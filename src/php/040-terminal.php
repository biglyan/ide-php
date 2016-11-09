<?php

$proc = nuLL;

class TerminalServer extends WebSocketServer {
  protected $maxBufferSize = 1048576;
  protected function process($user, $msgt) {
  	global $pipes;
  	if ($user->cookie == null) {
  		$msg = json_decode($msgt);
      $this->stdout("Attempting login with: " . $msgt);
  		if (check_login($msg->ijst, $msg->ijsh, file_get_contents(".cookie"))) {
  			$user->cookie = $msg;
        fwrite($pipes[0], "cd " . $msg->path ."\n");
        $this->stdout("Logged in.");
  		} else {
  		  $this->stderr("Bad login, disconnecting.");
  			$this->disconnect($user->socket);
  		}
  	} else {
  	  $this->stdout("Recieved: " . $msgt);
  		$wrote = fwrite($pipes[0], $msgt);
  		fflush($pipes[0]);
  		$this->stdout($wrote === FALSE? "Couldn't write to pipe 1" : "Wrote: " . $wrote);
  	}
  }

  protected function poll() {
  	global $pipes;

  	$output = fread($pipes[1], 4096);
  	if ($output === FALSE) {
  	  $this->stderr("read error on pipe 1: " . $output);
  	} else if ($output != null) {
  	  $this->stdout("Got data on pipe 1: " . $output);
  		foreach($this->users as $user) {
  			if ($user->cookie) {
          if (TERMINAL_FIX_CRLF) {
            $output = str_replace("\n", "\r\n", $output);
          }
  				$this->send($user, $output);
  				$this->stdout("Sent data.");
  			}
  		}
    } else {
      $this->stdout("=");
    }

  	$output = fread($pipes[2], 4096);
  	if ($output === FALSE) {
  	  $this->stdout("read error on pipe 2: " . $output);
  	} else if ($output != null) {
  	  $this->stdout("Got data on pipe 2: " . $output);
  		foreach($this->users as $user) {
  			if ($user->cookie) {
          if (TERMINAL_FIX_CRLF) {
            $output = str_replace("\n", "\r\n", $output);
          }
  				$this->send($user, $output);
  				$this->stdout("Sent data.");
  			}
  		}
  	} else {
  	  $this->stdout("=");
  	}
    if (feof($pipes[0]) || feof($pipes[1])) {
       $this->stdout("EOF for pipe 2.");
       die;
    }
  }
  protected function connected ($user) {
    $this->stdout($user->id.' -> the handshake response is sent to the client');
  }
  protected function closed ($user) {
    $this->stdout($user->id.' -> the connection is closed');
  }
}

function run_terminal() {
    global $proc, $pipes;
    $proc = proc_open(TERMINAL_COMMAND, array(
      0 => array("pty"),
      1 => array("pty"),
      2 => array("pty")
    ), $pipes);
    stream_set_blocking($pipes[0], 0);
    stream_set_blocking($pipes[1], 0);
    stream_set_blocking($pipes[2], 0);
    $term = new TerminalServer(TERMINAL_IP, TERMINAL_PORT);
    try {
      $term->run();
    } catch (Exception $e) {
      $term->stdout($e->getMessage());
    }
}

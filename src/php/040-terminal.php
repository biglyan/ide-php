<?php

$proc = nuLL;

class TerminalServer extends WebSocketServer {
  
  protected $maxBufferSize = 1048576;
  
  protected function process($user, $msg) {
  	global $pipes;
  	if ($user->cookie == null) {
  		$msg = json_decode($msg);
  		if (check_login($msg->ijst, $msg->ijsh, file_get_contents(".cookie"))) {
  			$user->cookie = $msg;
        fwrite($pipes[0], "cd " . $msg->path ."\n");
  		} else {
  			$this->disconnect($user->socket);
  		}
  	} else {
  		fwrite($pipes[0], $msg);
  	}
  }

  protected function poll() {
  	global $pipes;
  	if (!feof($pipes[1])) {
  		$output = fread($pipes[1], 4096);
  		if (strlen($output)) {
	  		foreach($this->users as $user) {
	  			if ($user->cookie) {
            if (TERMINAL_FIX_CRLF) {
                $output = str_replace("\n", "\r\n", $output);
            }
	  				$this->send($user, $output);
	  			}
	  		}
	  	}
  	 }
  	 if (!feof($pipes[2])) {
  		$output = fread($pipes[2], 4096);
  		if (strlen($output)) {
	  		foreach($this->users as $user) {
	  			if ($user->cookie) {
            if (TERMINAL_FIX_CRLF) {
                $output = str_replace("\n", "\r\n", $output);
            }
	  				$this->send($user, $output);
	  			}
	  		}
	  	}
  	 }
  }
  
  protected function connected ($user) { }
  
  protected function closed ($user) { }
}

function run_terminal() {
    global $proc, $pipes;
    $proc = proc_open(TERMINAL_COMMAND, array(
      0 => array("pipe", "r"),
      1 => array("pipe", "w"),
      2 => array("pipe", "w")
    ), $pipes);
    stream_set_blocking($pipes[0], 0);
    stream_set_blocking($pipes[1], 0);
    stream_set_blocking($pipes[2], 0);

    $term = new TerminalServer(TERMINAL_HOST, TERMINAL_PORT);
    try {
      $term->run();
    }
    catch (Exception $e) {
      $term->stdout($e->getMessage());
    }
}

<?php

$proc = nuLL;

class TerminalServer extends WebSocketServer {
  
  protected $maxBufferSize = 1048576;
  
  protected function process($user, $msgt) {
  	global $pipes;
  	if ($user->cookie == null) {
  		$msg = json_decode($msgt);
  		//file_put_contents("ide.log", "Attempting login with: " . $msgt, FILE_APPEND);
  		if (check_login($msg->ijst, $msg->ijsh, file_get_contents(".cookie"))) {
  			$user->cookie = $msg;
            fwrite($pipes[0], "cd " . $msg->path ."\n");
            //file_put_contents("ide.log", "Logged in.", FILE_APPEND);
  		} else {
  		    //file_put_contents("ide.log", "Bad login, disconnecting.", FILE_APPEND);
  			$this->disconnect($user->socket);
  		}
  	} else {
  	    //file_put_contents("ide.log", "Recieved: " . $msgt, FILE_APPEND);
  		$wrote = fwrite($pipes[0], $msgt);
  		fflush($pipes[0]);
  		//file_put_contents("ide.log", ($wrote === FALSE? "Couldn't write to pipe 1" : "Wrote: " . $wrote) , FILE_APPEND);
  	}
  }

  protected function poll() {
  	global $pipes;

  	$output = fread($pipes[1], 4096);
  	if ($output === FALSE) {
  	    //file_put_contents("ide.log", "read error on pipe 1: " . $output, FILE_APPEND);
  	} else if ($output != null) {
  	    //file_put_contents("ide.log", "Got data on pipe 1: " . $output, FILE_APPEND);
  		foreach($this->users as $user) {
  			if ($user->cookie) {
                if (TERMINAL_FIX_CRLF) {
                    $output = str_replace("\n", "\r\n", $output);
                }
  				$this->send($user, $output);
  				//file_put_contents("ide.log", "Sent data.", FILE_APPEND);
  			}
  		}
  	}

  	$output = fread($pipes[2], 4096);
  	if ($output === FALSE) {
  	    //file_put_contents("ide.log", "read error on pipe 2: " . $output, FILE_APPEND);
  	} else if ($output != null) {
  	    //file_put_contents("ide.log", "Got data on pipe 2: " . $output, FILE_APPEND);
  		foreach($this->users as $user) {
  			if ($user->cookie) {
                if (TERMINAL_FIX_CRLF) {
                    $output = str_replace("\n", "\r\n", $output);
                }
  				$this->send($user, $output);
  				//file_put_contents("ide.log", "Sent data.", FILE_APPEND);
  			}
  		}
  	} else {
  	    //file_put_contents("ide.log", "=", FILE_APPEND);
  	}
  	 if (feof($pipes[0]) || feof($pipes[1])) {
  	     //file_put_contents("ide.log", "EOF for pipe 2.", FILE_APPEND);
  	     die;
  	 }
  }
  
  protected function connected ($user) { }
  
  protected function closed ($user) { }
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
    }
    catch (Exception $e) {
      $term->stdout($e->getMessage());
    }
}

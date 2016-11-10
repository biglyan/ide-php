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
    // stdout
    $output = fread($pipes[1], 4096);
    if ($output === FALSE) {
      $this->stderr("read error on pipe 1");
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
      //$this->stdout("=");
    }

    // stderr
    $output = fread($pipes[2], 4096);
    if ($output === FALSE) {
      $this->stdout("read error on pipe 2");
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
      //$this->stdout("=");
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

class EchoServer extends WebSocketServer {
  //protected $maxBufferSize = 1048576; //1MB... overkill for an echo server, but potentially plausible for other applications.

  protected function process($user, $message) {
    $this->send($user, $message);
  }

  protected function connected($user) {
    // Do nothing: This is just an echo server, there's no need to track the user.
    // However, if we did care about the users, we would probably have a cookie to
    // parse at this step, would be looking them up in permanent storage, etc.
  }

  protected function closed($user) {
    // Do nothing: This is where cleanup would go, in case the user had any sort of
    // open files or other objects associated with them. This runs after the socket
    // has been closed, so there is no need to clean up the socket itself here.
  }
}

function run_echo() {
  $echo = new EchoServer('0.0.0.0', '9000');
  try {
    $echo->run();
  } catch (Exception $ex) {
    $echo->stderr($ex->getMessage());
  }
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
    $term = new TerminalServer(TERMINAL_IP, TERMINAL_PORT);
    try {
      $term->run();
    } catch (Exception $e) {
      $term->stderr($e->getMessage());
    }
}

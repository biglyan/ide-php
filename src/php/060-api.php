<?php
function api_read($p) {
    require_login();
    $path = set_cwd(dirname($p->path));
    $file = basename($p->path);
    return array("contents" => file_exists($file) ? file_get_contents($file) : "");
}

function api_write($p) {
    require_login();
    $path = set_cwd(dirname($p->path));
    file_put_contents(basename($p->path), $p->content);
    return null;
}

function api_write_data_url($p) {
    require_login();
    $path = set_cwd(dirname($p->path));
    $content = base64_decode(substr($p->content, strpos($p->content, ',')));
    file_put_contents(basename($p->path), $content);
    return null;
}

function api_delete($p) {
    require_login();
    $path = set_cwd(dirname($p->path));
    unlink($p->path);
    return null;
}

function api_browse($p) {
    require_login();
    $path = set_cwd($p->path);
    $items =  glob("*");
    $files = array();
    $folders = array();
    foreach($items as $item) {
        if (is_dir($item)) {
            $folders[] = array("name" => basename($item), "modified" => date ("d-m-Y H:i:s", filemtime($item)), "size" => "");
        } else {
            $files[] = array("name" => basename($item), "modified" => date ("d-m-Y H:i:s", filemtime($item)), "size" => readable_filesize(filesize($item)));
        }
    }
    return array("path" => $path, "basename" => basename($path), "files" => $files, "folders" => $folders);
}

function api_terminal($p) {
    require_login();
    $cmd = "php ide.php terminal " . TERMINAL_IP . ":" . TERMINAL_PORT;
    if (strpos(shell_exec("ps aux"), $cmd) == false) {
        daemonize($cmd);
    }
    return array("url" => TERMINAL_WEBSOCKET_URL);
}

function api_login($p) {
    $passhash = file_get_contents(".passwd");
    //return array("p" => $pass, "h" => $passhash);
    if (password_verify($p->password, $passhash)) {
        $time = time();
        $clientInfo = $_SERVER["REMOTE_ADDR"] . $_SERVER["HTTP_USER_AGENT"];
        $hash = sha1($time . $clientInfo . ENCRYPTION_SALT);
        file_put_contents(".cookie", $clientInfo);
        setcookie("ijsh", $hash, $time + (60 * 60 * 24), "/", null, null, false);
        setcookie("ijst", $time, $time + (60 * 60 * 24), "/", null, null, false);
    } else {
        failure("NOT_LOGGED_IN");
    }
    return null;
}

function api_logout($p) {
    setcookie("ijsh", null, $time - (60 * 60 * 24), "/", null, null, false);
    setcookie("ijst", null, $time - (60 * 60 * 24), "/", null, null, false);
}

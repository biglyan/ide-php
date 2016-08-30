<?php

define("WORKING_DIRECTORY", ".");
define("WEB_URL", "http://localhost:9002/ide.php");
define("TERMINAL_HOST", "0.0.0.0");
define("TERMINAL_PORT", "9001");
define("TERMINAL_COMMAND", "/bin/sh -i");
define("TERMINAL_WEBSOCKET_URL", "ws://localhost:9001");
define("TERMINAL_FIX_CRLF", true);
define("ENCRYPTION_KEY", "6UQkyq8wtb09gDRLoVrigO7WneJE00b3");
define("ENCRYPTION_SALT", "VrigO7WneJE00b36UQkyq8wtb09gDRLo");

function success($r = null) {
    echo json_encode(array("error" => null, "result" => $r)); die;
}

function failure($msg) {
    echo json_encode(array("error" => $msg, "result" => null)); die;
}

function set_cwd($targetPath) {
    chdir(WORKING_DIRECTORY);
    $wd = getcwd();
    chdir($targetPath);
    $targetPath = getcwd();
    if (strlen($wd) > strlen($targetPath)) { failure("Cannot access parent folders."); }
    return $targetPath;
}

function encrypt($text) {
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, ENCRYPTION_KEY , $text, MCRYPT_MODE_CBC, $iv);
    return base64_encode($iv . $ciphertext);
}

function decrypt($text) {
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
    $ciphertext_dec = base64_decode($text);
    $iv_dec = substr($ciphertext_dec, 0, $iv_size);
    $ciphertext_dec = substr($ciphertext_dec, $iv_size);
    $ciphertext_padded = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, ENCRYPTION_KEY, $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec);
    return preg_replace( "/\p{Cc}*$/u", "", $ciphertext_padded);
}

function check_login($ijst=null, $ijsh=null, $userInfo=null) {
    $time = $ijst? $ijst : intval($_COOKIE["ijst"]);
    $checkHash = $ijsh? $ijsh : $_COOKIE["ijsh"];
    $userInfo = $userInfo? $userInfo : ($_SERVER["REMOTE_ADDR"] . $_SERVER["HTTP_USER_AGENT"]);
    $hash = sha1($time . $userInfo . ENCRYPTION_SALT);
    return $hash == $checkHash;
}

function require_login() {
    if (!check_login()) {
        failure("NOT_LOGGED_IN");
    }
}

function mime_type($filename) {
    return trim(shell_exec("file -b --mime-type -m /usr/share/misc/magic " . escapeshellcmd($filename)));
}

function api_read($p) {
    require_login();
    $path = set_cwd(dirname($p->path));
    $file = basename($p->path);
    return array("contents" => file_exists($file)? file_get_contents($file) : "");
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
            $folders[] = basename($item);
        } else {
            $files[] = basename($item);
        }
    }
    return array("path" => $path, "basename" => basename($path), "files" => $files, "folders" => $folders);
}

function api_terminal($p) {
    require_login();
    $ps = shell_exec("ps aux");
    if (strpos($ps, "php ide.php terminal") == false) {
        $pid = shell_exec("nohup php ide.php terminal 2> /dev/null & echo $!");
    }
    return array("url" => TERMINAL_WEBSOCKET_URL);
}

function api_login($p) {
    if (password_verify($p->password, file_get_contents(".passwd"))) {
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

if (php_sapi_name() == "cli") { 
    if ($argv[1] == "password") {
        file_put_contents(".passwd", password_hash($argv[2], PASSWORD_DEFAULT));
    } else if ($argv[1] == "terminal") {
        require("terminal.php");
    }
    die; 
}

if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    header('Content-Type', 'application/json');
    header('Access-Control-Allow-Origin', '*');
    if ($_SERVER['REQUEST_METHOD'] == "OPTIONS") { die; }
    $post = json_decode(file_get_contents('php://input'));
    $cmd = "api_" . $post->cmd;
    if (!function_exists($cmd)) { failure("No such method: " . $cmd);  }
    success($cmd($post));
    die;
}

$app = "browser";
$path = ".";

if (isset($_GET["download"])) {
    $downloadPath = $_GET["download"];
    set_cwd(dirname($downloadPath));
    $filename = basename($downloadPath);
    header("Content-Type: " . mime_type($filename));
    echo file_get_contents($filename);
    die;
} else if (isset($_GET["browser"])) {
    $app = "browser";
    $path = $_GET["browser"];
} else if (isset($_GET["editor"])) {
    $app = "editor";
    $path = $_GET["editor"];
} else if (isset($_GET["console"])) {
    $app = "console";
    $path = $_GET["console"];
}

?><!DOCTYPE html>
<html lang="en">
<head>
<title>ide.js</title>
<link href="res/xterm.css" rel="stylesheet">
<link href="res/styles.css" rel="stylesheet">
</head>
<body>

<div class="container">


<div class="toolbar">
<? if ($app == "browser") { ?>
    <button id="home">Home</button>
    <button id="newfile">New File</button>
    <button id="upload">Upload</button>
    <input type="file" id="file" style="display:none"/>
<? } else if ($app == "editor") { ?>
    <button id="save">Save</button>
    <input id="searchtext" placeholder="Search..."/>
    <button id="findnext">Find Next</button>
    <input id="replacetext" placeholder="Replace..."/>
    <button id="replacenext">Replace Next</button>
    <button id="replaceall">Replace All</button>
    <button id="goto">Go To Line</button>
<? } else if ($app == "console") { ?>
<? } ?>
</div>


<div class="content">
<? if ($app == "browser") { ?>
    <table class="browser" id="browser"></table>
<? } else if ($app == "editor") { ?>
    <div class="editor" id="editor"></div>
<? } else if ($app == "console") { ?>
    <div class="console" id="console"></div>
<? } ?>
</div>


<script src='res/xterm.js'></script>
<script src='res/attach.js'></script>
<script src='res/fit.js'></script>
<script src="res/app_browser.js"/></script>
<script src="res/app_editor.js"/></script>
<script src="res/app_console.js"/></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/require.js/2.2.0/require.js"></script>

<script>

apiUrl = "<?=WEB_URL?>";

function getCookie(a, b) {
    b = document.cookie.match('(^|;)\\s*' + a + '\\s*=\\s*([^;]+)');
    return b ? b.pop() : '';
}

function api(cmd, data, deferred) {
    var d = deferred? deferred : $.Deferred();
    $.ajax(apiUrl, { method:"post", dataType:'json', contentType:'application/json', data: JSON.stringify($.extend({}, data, {cmd: cmd})) })
        .then(function(result) {
            result = result? result : {};
            if (result.error) {
                if (result.error == "NOT_LOGGED_IN") {
                    api("login", {password: prompt("Please enter your password.")})
                    .then(function() {
                        api(cmd, data, d);
                    });
                } else {
                    var msg = "API Error: " + result.error;
                    console.error(msg);
                    alert(msg);
                    d.reject(msg);
                }
            } else {
                d.resolve(result.result);
            }
        }, function(xhr, status, err) {
            var msg = "AJAX Error: " + status + " " + xhr.responseText;
            console.error(msg);
            alert(msg);
            d.reject(msg);
        });
    return d;
}

requirejs.config({
    appDir: ".",
    baseUrl: "https://cdnjs.cloudflare.com/ajax/libs/",
    paths: { jquery: ['jquery/3.1.0/jquery'], 'ace': ['ace/1.2.5/ace'] }
});

require(['jquery', 'ace'], function($) {
    window.$ = $;
    setTimeout(app_<?=$app?>.bind(null, "<?=$path?>"), 100);
});

</script>

</body>
</html>
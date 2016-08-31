<?php

if (php_sapi_name() == "cli") { 
    if ($argv[1] == "password") {
        file_put_contents(".passwd", password_hash($argv[2], PASSWORD_DEFAULT));
    } else if ($argv[1] == "terminal") {
        run_terminal();
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
<link id="favicon" rel="shortcut icon" type="image/png" href="?png=<?=$app?>.png" />
<link href="?css" rel="stylesheet">
<script src='?js'></script>
</head>
<body>

<div class="container">


<div class="toolbar">
<? if ($app == "browser") { ?>
    <button id="home">Home</button>
    <button id="newfile">New File</button>
    <button id="upload">Upload</button>
    <button id="logout">Logout</button>
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/require.js/2.2.0/require.js"></script>

<script>

apiUrl = "<?=WEB_URL?>";    

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
<?php

if (php_sapi_name() == "cli") {
    if ($argv[1] == "password") {
        echo "Please enter new password: ";
        $passwd = trim(fgets(STDIN));
        if ($passwd == null || strlen($passwd) == 0) { echo "Please enter a password."; die; }
        file_put_contents(".passwd", password_hash($passwd, PASSWORD_DEFAULT));
        echo "Password set.\n";
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
<html lang="zh-CN">
<head>
<title>ide.js</title>
<link id="favicon" rel="shortcut icon" type="image/png" href="?png=<?=$app?>.png" />
<link href="?css" rel="stylesheet">
<script src='?js'></script>
</head>
<body>
<div class="container">
<?php if ($app == "browser") { ?>
<div class="toolbar">
    <button id="home">首页</button>
    <button id="newfile">新建文件</button>
    <button id="upload">上传</button>
    <button id="logout">退出</button>
    <input type="file" id="file" style="display:none"/>
</div>
<div class="content">
    <table class="browser" id="browser"></table>
</div>
<?php } else if ($app == "editor") { ?>
<div class="toolbar">
    <button id="save">保存</button>
    <input id="searchtext" placeholder="查找..."/>
    <button id="findnext">查找下一个</button>
    <input id="replacetext" placeholder="替换..."/>
    <button id="replacenext">替换下一个</button>
    <button id="replaceall">替换所有</button>
    <button id="goto">跳转</button>
</div>
<div class="content">
    <div class="editor" id="editor"></div>
</div>
<?php } else if ($app == "console") { ?>
<div class="console" id="console"></div>
<?php } ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/require.js/2.2.0/require.js"></script>
<script>
apiUrl = "<?=WEB_URL?>";
/*requirejs.config({
    appDir: ".",
    baseUrl: "https://cdnjs.cloudflare.com/ajax/libs/",
    paths: { 'ace': ['ace/1.2.5/ace'] }
});

require(['ace'], function() {
    setTimeout(app_<?=$app?>.bind(null, "<?=$path?>"), 1);
});
*/
setTimeout(app_<?=$app?>.bind(null, "<?=$path?>"), 1);
</script>
</body>
</html>

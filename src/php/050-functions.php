<?php
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

function readable_filesize($bytes, $decimals = 2) {
    $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}

function daemonize($cmd) {
    $uname == php_uname('s');
    if ($uname == "Darwin") {
        pclose(popen('nohup ' . $cmd . ' &', 'r'));
    } else {
        exec('bash -c "exec nohup ' . $cmd . ' > /dev/null 2>&1 &"');
    }
}
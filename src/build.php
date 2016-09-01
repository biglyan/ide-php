<?php
ob_start();

echo "<?php\n";

foreach(glob("cfg/*.php") as $php) { 
	$f = fopen($php, "r");
	fgets($f); // discard first line with <?php
	while(!feof($f)) { echo fgets($f); } 
	fclose($f); 
}

echo 'if (isset($_GET["css"])) { header("Content-Type: text/css"); ?'.'>';
foreach(glob("css/*.css") as $css) { readfile($css); }
echo "<"."? die; }\n";

echo 'if (isset($_GET["js"])) { header("Content-Type: application/javascript"); ?'.'>';
foreach(glob("js/lib/*.js") as $js) { readfile($js); echo ";"; }
foreach(glob("js/*.js") as $js) { readfile($js); echo ";"; }
echo "<"."? die; }\n";

echo 'if (isset($_GET["png"])) { 
	header("Content-Type: image/png"); 
	switch ($_GET["png"]) {
';
foreach(glob("png/*.png") as $png) { 
	echo "case '".basename($png)."': echo base64_decode('".base64_encode(file_get_contents($png))."'); break;\n";
}
echo "} die; }\n";

foreach(glob("php/*.php") as $php) { 
	$f = fopen($php, "r");
	fgets($f); // discard first line with <?php
	while(!feof($f)) { echo fgets($f); } 
	fclose($f); 
}

file_put_contents("../ide.php", ob_get_clean());
<?php
// PHP's development server: php -S localhost:8080 -t public public/router.php
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$file=$path!==false?realpath(__DIR__.rawurldecode($path)):false;
if($file && strpos(str_replace('\\','/',$file),str_replace('\\','/',__DIR__).'/')===0 && is_file($file))return false;
if(is_string($path) && preg_match('~^/(?:assets|uploads)/~',$path)){http_response_code(404);exit;}
// The built-in server reports dotted paths as SCRIPT_NAME; Yii needs the entry script.
$_SERVER['SCRIPT_NAME']='/index.php';$_SERVER['PHP_SELF']='/index.php';$_SERVER['SCRIPT_FILENAME']=__DIR__.'/index.php';
require __DIR__.'/index.php';

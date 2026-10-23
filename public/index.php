<?php
if(!getenv('SHOP_DSN')&&!is_file(dirname(__DIR__).'/protected/config/local.php')&&!is_file(dirname(__DIR__).'/protected/runtime/install.lock')&&!is_file(dirname(__DIR__).'/protected/runtime/store.sqlite')){
    header('Location: '.rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/index.php')),'/').'/install.php',true,302);exit;
}
$yii=dirname(__DIR__).'/vendor/yiisoft/yii/framework/yii.php';
if(!is_file($yii)) {
    http_response_code(503);
    exit('Yii framework not installed. Run composer install in the project root.');
}
require_once $yii;
Yii::createWebApplication(dirname(__DIR__).'/protected/config/main.php')->run();

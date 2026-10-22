<?php
$yii=dirname(__DIR__).'/vendor/yiisoft/yii/framework/yii.php';
if(!is_file($yii)) {
    http_response_code(503);
    exit('Yii framework not installed. Run composer install in the project root.');
}
require_once $yii;
Yii::createWebApplication(dirname(__DIR__).'/protected/config/main.php')->run();

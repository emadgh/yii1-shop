<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once dirname(__DIR__).'/vendor/yiisoft/yii/framework/yii.php';
$config=require dirname(__DIR__).'/protected/config/main.php';
unset($config['defaultController']);
$config['components']=array('db'=>$config['components']['db']);
Yii::createConsoleApplication($config);
Yii::app()->db->active=true;

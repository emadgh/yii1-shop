<?php
// Copy to local.php to override main configuration. Keep outside public/.
if(getenv('SHOP_DSN'))return array();
return array(
  'components'=>array('db'=>array(
    'connectionString'=>'mysql:host=127.0.0.1;port=3306;dbname=yii1_shop;charset=utf8mb4',
    'username'=>'yii_user','password'=>'CHANGE_ME',
    'initSQLs'=>array(),
  )),
);

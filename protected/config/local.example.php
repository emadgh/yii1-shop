<?php
// Copy to local.php to override main configuration. Keep outside public/.
return array(
  'components'=>array('db'=>array(
    'connectionString'=>'mysql:host=127.0.0.1;dbname=yii_store;charset=utf8mb4',
    'username'=>'yii_user','password'=>'CHANGE_ME',
    'initSQLs'=>array(),
  )),
);

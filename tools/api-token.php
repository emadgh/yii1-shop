<?php
require __DIR__.'/bootstrap.php';
$options=getopt('',array('user:','name:','scopes:','days:','output:','url:','enable'));
if(empty($options['user'])||empty($options['output'])){fwrite(STDERR,"Usage: php tools/api-token.php --user=EMAIL --name=Codex --scopes=content:read,content:write --output=PRIVATE_JSON [--enable]\n");exit(2);}
$user=User::model()->findByAttributes(array('email'=>$options['user']));if(!$user)throw new Exception('User not found');
$output=$options['output'];if(file_exists($output))throw new Exception('Output already exists; preserve or revoke the previous credential first.');
$issued=ApiTokenService::issue($user->id,$options['name']??'AI client',explode(',',$options['scopes']??'content:read,content:write,media:write'),(int)($options['days']??90));
try{
    $handle=fopen($output,'x');if(!$handle)throw new Exception('Cannot create private config');
    fwrite($handle,json_encode(array('url'=>$options['url']??'http://127.0.0.1:8080/api/v1/','token'=>$issued['token']),JSON_PRETTY_PRINT));fclose($handle);@chmod($output,0600);
    if(isset($options['enable']))ShopSettings::set('ai_api_enabled','1');
    echo 'Private credential written; token ID '.$issued['id'].". Token was not printed.\n";
}catch(Exception $e){Yii::app()->db->createCommand()->update('api_tokens',array('revoked_at'=>date('Y-m-d H:i:s')),'id=:id',array(':id'=>$issued['id']));throw $e;}

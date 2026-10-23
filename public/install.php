<?php
ini_set('display_errors','0');
header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');header('X-Frame-Options: DENY');header('Referrer-Policy: no-referrer');header('X-Robots-Tag: noindex, nofollow');
require dirname(__DIR__).'/protected/install/InstallerService.php';
$installer=new InstallerService(dirname(__DIR__));$locked=$installer->installed();$complete=$locked&&isset($_GET['complete']);$error='';$success=false;
$checks=$installer->requirements();$ready=!in_array(false,array_column($checks,'ok'),true);
$host=$_SERVER['HTTP_HOST']??'localhost';if(!preg_match('/^[a-zA-Z0-9.\[\]:-]+$/D',$host))$host='localhost';
$base=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/install.php')),'/');if($base==='.')$base='';
$values=array('site_name'=>'','site_description'=>'','site_url'=>(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off'?'https':'http').'://'.$host.$base,'db_host'=>'localhost','db_port'=>'3306','db_name'=>'','db_user'=>'','admin_name'=>'','admin_email'=>'','sample_data'=>'0');
if(!$locked||$complete){
    session_name('BAZAARCHE_INSTALL');session_set_cookie_params(array('httponly'=>true,'samesite'=>'Strict','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off'));session_start();
}
if(!$locked){
    if(empty($_SESSION['installer_csrf']))$_SESSION['installer_csrf']=bin2hex(random_bytes(32));
    if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
        $ajax=($_SERVER['HTTP_X_REQUESTED_WITH']??'')==='XMLHttpRequest';$status=422;
        try{
            if(!isset($_POST['csrf'])||!is_string($_POST['csrf'])||!hash_equals($_SESSION['installer_csrf'],$_POST['csrf'])){ $status=400;throw new InvalidArgumentException('نشست نصب معتبر نیست؛ صفحه را تازه کنید.'); }
            if(($_POST['action']??'')==='check'){$message=$installer->checkDatabase($_POST);$success=true;}
            else{if(!$ready)throw new InvalidArgumentException('ابتدا پیش‌نیازهای مشخص‌شده را آماده کنید.');$result=$installer->install($_POST);$_SESSION['installer_cache_available']=$result['cache_available'];unset($_SESSION['installer_csrf']);$success=true;$message='نصب کامل شد.';}
        }catch(Throwable $exception){$error=$installer->errorMessage($exception);}
        if($ajax){http_response_code($success?200:$status);header('Content-Type: application/json; charset=utf-8');echo json_encode(array('ok'=>$success,'message'=>$success?$message:$error,'redirect'=>$success&&($_POST['action']??'')!=='check'?'install.php?complete=1':null),JSON_UNESCAPED_UNICODE);exit;}
        if($success&&($_POST['action']??'')!=='check'){header('Location: install.php?complete=1',true,303);exit;}
        foreach($values as $key=>$default)if(isset($_POST[$key])&&is_string($_POST[$key]))$values[$key]=function_exists('mb_substr')?mb_substr($_POST[$key],0,500):substr($_POST[$key],0,500);
    }
}elseif(!$complete){http_response_code(423);}
$escape=function($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');};
require dirname(__DIR__).'/protected/install/view.php';

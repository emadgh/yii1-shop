<?php
require __DIR__.'/bootstrap.php';
$rows=Yii::app()->db->createCommand("SELECT * FROM notifications WHERE state='pending' AND attempts<5 AND available_at<=:now ORDER BY id LIMIT 25")->queryAll(true,array(':now'=>date('Y-m-d H:i:s')));
foreach($rows as $row){
 if($row['channel']==='email'&&!getenv('SHOP_SMTP_HOST'))continue;if($row['channel']==='sms'&&!getenv('SHOP_KAVENEGAR_KEY'))continue;
 $db=Yii::app()->db;$claimed=$db->createCommand("UPDATE notifications SET state='processing',attempts=attempts+1 WHERE id=:id AND state='pending'")->execute(array(':id'=>$row['id']));if(!$claimed)continue;
 try{
  if($row['channel']==='email'){$mail=new \PHPMailer\PHPMailer\PHPMailer(true);$mail->isSMTP();$mail->Host=getenv('SHOP_SMTP_HOST');$mail->Port=(int)(getenv('SHOP_SMTP_PORT')?:587);$mail->SMTPAuth=true;$mail->Username=getenv('SHOP_SMTP_USER');$mail->Password=getenv('SHOP_SMTP_PASSWORD');$mail->SMTPSecure=$mail->Port===465?'ssl':'tls';$mail->Timeout=20;$mail->CharSet='UTF-8';$mail->setFrom(getenv('SHOP_SMTP_FROM'),ShopSettings::get('site_name'));$mail->addAddress($row['recipient']);$mail->Subject='وضعیت سفارش';$mail->Body=$row['message'];$mail->send();}
  else{$key=getenv('SHOP_KAVENEGAR_KEY');if(!preg_match('/^[a-zA-Z0-9]+$/D',$key))throw new Exception('SMS key invalid');$ctx=stream_context_create(array('http'=>array('method'=>'POST','header'=>'Content-Type: application/x-www-form-urlencoded','content'=>http_build_query(array('receptor'=>$row['recipient'],'message'=>$row['message'],'sender'=>getenv('SHOP_KAVENEGAR_SENDER'))),'timeout'=>20)));$reply=json_decode(@file_get_contents('https://api.kavenegar.com/v1/'.$key.'/sms/send.json',false,$ctx),true);if((int)($reply['return']['status']??0)!==200)throw new Exception('SMS delivery failed');}
  $db->createCommand()->update('notifications',array('state'=>'sent'),'id=:id',array(':id'=>$row['id']));echo 'Sent notification '.$row['id']."\n";
 }catch(Exception $e){$db->createCommand()->update('notifications',array('state'=>$row['attempts']>=4?'failed':'pending','available_at'=>date('Y-m-d H:i:s',time()+300)),'id=:id',array(':id'=>$row['id']));echo 'Notification '.$row['id']." needs retry; provider details withheld.\n";}
}

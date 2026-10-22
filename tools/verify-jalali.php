<?php
if(PHP_SAPI!=='cli')exit(1);
$root=dirname(__DIR__);$copy=$root.'/protected/runtime/jalali-test-'.bin2hex(random_bytes(5)).'.sqlite';copy($root.'/protected/runtime/store.sqlite',$copy);putenv('SHOP_DSN=sqlite:'.$copy);require __DIR__.'/bootstrap.php';$checks=0;
function calendarCheck($ok,$label){global $checks;if(!$ok)throw new Exception('FAILED '.$label);echo 'PASS '.$label."\n";$checks++;}
try{
 calendarCheck(JalaliDate::format('2024-03-20 23:59:59')==='1403/01/01 23:59','Nowruz display retains Tehran wall time');
 calendarCheck(JalaliDate::parse('۱۳۹۹/۱۲/۳۰ ۲۳:۵۹:۵۹')==='2021-03-20 23:59:59','Persian digits and leap-year last day');
 calendarCheck(JalaliDate::parse('١٤٠٤/٠١/٠١',false)==='2025-03-21','Arabic digits accepted');
 foreach(array('1400/12/30','1404/13/01','1404/01/32','1404/00/01','1404/01/00','2025-03-21',"1404/01/01' OR 1=1",array('1404/01/01')) as $input){$rejected=false;try{JalaliDate::parse($input,false);}catch(Exception $e){$rejected=true;}calendarCheck($rejected,'invalid date rejected '.json_encode($input));}
 $rejected=false;try{JalaliDate::parse('1404/01/01 24:00');}catch(Exception $e){$rejected=true;}calendarCheck($rejected,'invalid time rejected');
 foreach(array('2024-03-19','2024-03-20','2025-03-20','2025-03-21','2026-10-22') as $day)calendarCheck(JalaliDate::parse(JalaliDate::format($day,false),false)===$day,'date round trip '.$day);
 Yii::app()->setComponent('request',array('class'=>'CHttpRequest','scriptUrl'=>'/index.php','baseUrl'=>''));$db=Yii::app()->db;$ids=$db->createCommand('SELECT id FROM orders ORDER BY id LIMIT 2')->queryColumn();calendarCheck(count($ids)===2,'order boundary fixtures available');$db->createCommand()->update('orders',array('created_at'=>'2025-03-19 00:00:00'));$db->createCommand()->update('orders',array('created_at'=>'2025-03-20 23:59:59'),'id=:id',array(':id'=>$ids[0]));$db->createCommand()->update('orders',array('created_at'=>'2025-03-21 00:00:00'),'id=:id',array(':id'=>$ids[1]));
 $definition=AdminTables::definition('orders');foreach(array('1403/12/30'=>$ids[0],'1404/01/01'=>$ids[1]) as $day=>$id){$_GET=array('Grid'=>array('orders'=>array('created_at'=>$day)));$rows=(new AdminTableSearch('orders',$definition['fields']))->provider($definition)->getData();calendarCheck(count($rows)===1&&(int)$rows[0]->id===(int)$id,'Jalali filter bounds '.$day);}
 echo "All $checks calendar checks passed on isolated database.\n";
}finally{Yii::app()->db->active=false;@unlink($copy);}

<?php
class VisitService {
 public static function enabled(){return ShopSettings::get('analytics_enabled','1')==='1';}
 public static function summary($days=7){
  $days=max(1,min(90,(int)$days));$today=strtotime('today');$start=date('Y-m-d 00:00:00',$today-86400*($days-1));$end=date('Y-m-d 00:00:00',$today+86400);
  $rows=Yii::app()->db->createCommand('SELECT SUBSTR(created_at,1,10) day,COUNT(*) views,COUNT(DISTINCT visitor_hash) visitors FROM page_visits WHERE created_at>=:start AND created_at<:end GROUP BY SUBSTR(created_at,1,10) ORDER BY day')->queryAll(true,array(':start'=>$start,':end'=>$end));
  $map=array();foreach($rows as $row)$map[$row['day']]=$row;$daily=array();$views=0;$visitors=0;
  for($i=$days-1;$i>=0;$i--){$day=date('Y-m-d',$today-86400*$i);$row=$map[$day]??array('day'=>$day,'views'=>0,'visitors'=>0);$row['views']=(int)$row['views'];$row['visitors']=(int)$row['visitors'];$daily[]=$row;$views+=$row['views'];$visitors+=$row['visitors'];}
  return array('days'=>$days,'views'=>$views,'visitors'=>$visitors,'daily'=>$daily,'today'=>end($daily),'enabled'=>self::enabled());
 }
 private static function secret(){
  $path=Yii::getPathOfAlias('application.runtime').'/analytics.key';if(!is_file($path)){$handle=@fopen($path,'x');if($handle){fwrite($handle,bin2hex(random_bytes(32)));fclose($handle);@chmod($path,0600);}}
  $secret=@file_get_contents($path);if(!$secret)throw new Exception('Analytics key unavailable');return $secret;
 }
 public static function record($event,$path,$referrer,$agent){
  if(!self::enabled() || AccessService::isAdmin() || ThemeService::preview() || ($_SERVER['HTTP_DNT']??'')==='1' || ($_SERVER['HTTP_SEC_GPC']??'')==='1' || preg_match('/bot|spider|crawler|curl|wget|python/i',$agent))return false;
  if(!is_string($event) || !preg_match('/^[a-f0-9-]{20,64}$/D',$event) || !is_string($path) || strlen($path)>255 || !preg_match('~^/(?:$|shop(?:/|$)|product/|category/|blog(?:/|$)|collection/)~',$path) || strpos($path,'?')!==false || strpos($path,'#')!==false)throw new CHttpException(422,'Invalid analytics event');
  $now=microtime(true);$last=(float)Yii::app()->session['visit_last'];if($now-$last<.25)return false;Yii::app()->session['visit_last']=$now;
  $hash=hash_hmac('sha256',Yii::app()->session->sessionID.date('Y-m-d'),self::secret());$host=parse_url((string)$referrer,PHP_URL_HOST)?:'';if(strlen($host)>190)$host='';
  $device=preg_match('/ipad|tablet/i',$agent)?'tablet':(preg_match('/mobile|android|iphone/i',$agent)?'mobile':'desktop');
  $prefix=Yii::app()->db->driverName==='sqlite'?'INSERT OR IGNORE':'INSERT IGNORE';
  Yii::app()->db->createCommand($prefix.' INTO page_visits (event_id,visitor_hash,path,referrer_host,device,created_at) VALUES (:event,:visitor,:path,:referrer,:device,:created)')->execute(array(':event'=>$event,':visitor'=>$hash,':path'=>$path,':referrer'=>$host,':device'=>$device,':created'=>date('Y-m-d H:i:s')));
  if(!Yii::app()->session['visit_pruned_'.date('Y-m-d')]){$days=max(7,min(365,(int)ShopSettings::get('analytics_retention_days',90)));Yii::app()->db->createCommand('DELETE FROM page_visits WHERE created_at<:cutoff')->execute(array(':cutoff'=>date('Y-m-d H:i:s',time()-86400*$days)));Yii::app()->session['visit_pruned_'.date('Y-m-d')]=1;}
  return true;
 }
}

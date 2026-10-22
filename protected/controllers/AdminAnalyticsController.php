<?php
class AdminAnalyticsController extends AdminController {
 public function actionIndex(){
  $days=(int)Yii::app()->request->getQuery('days',30);if(!in_array($days,array(7,30,90),true))$days=30;$cutoff=date('Y-m-d 00:00:00',time()-86400*($days-1));$db=Yii::app()->db;
  $totals=$db->createCommand('SELECT COUNT(*) views,COUNT(DISTINCT visitor_hash) visitors FROM page_visits WHERE created_at>=:cutoff')->queryRow(true,array(':cutoff'=>$cutoff));
  $daily=$db->createCommand('SELECT SUBSTR(created_at,1,10) day,COUNT(*) views,COUNT(DISTINCT visitor_hash) visitors FROM page_visits WHERE created_at>=:cutoff GROUP BY SUBSTR(created_at,1,10) ORDER BY day')->queryAll(true,array(':cutoff'=>$cutoff));
  $pages=$db->createCommand('SELECT path,COUNT(*) views FROM page_visits WHERE created_at>=:cutoff GROUP BY path ORDER BY views DESC LIMIT 20')->queryAll(true,array(':cutoff'=>$cutoff));
  $sources=$db->createCommand('SELECT referrer_host,COUNT(*) views FROM page_visits WHERE created_at>=:cutoff GROUP BY referrer_host ORDER BY views DESC LIMIT 10')->queryAll(true,array(':cutoff'=>$cutoff));
  $devices=$db->createCommand('SELECT device,COUNT(*) views FROM page_visits WHERE created_at>=:cutoff GROUP BY device')->queryAll(true,array(':cutoff'=>$cutoff));
  $this->pageTitle='آمار بازدید';$this->render('//admin/analytics/index',compact('days','totals','daily','pages','sources','devices'));
 }
}

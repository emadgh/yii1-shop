<?php
class AdminAnalyticsController extends AdminController {
 public function actionIndex(){
  $days=(int)Yii::app()->request->getQuery('days',30);if(!in_array($days,array(7,30,90),true))$days=30;
  $this->pageTitle='آمار بازدید';$this->render('//admin/analytics/index',array('days'=>$days));
 }
}

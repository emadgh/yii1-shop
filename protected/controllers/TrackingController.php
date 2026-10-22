<?php
class TrackingController extends AppController {
 public function actionIndex(){
  header('Cache-Control: no-store');header('Referrer-Policy: no-referrer');header('X-Robots-Tag: noindex, nofollow');$order=null;$error='';$token='';
  if(Yii::app()->request->isPostRequest){$token=Yii::app()->request->getPost('token','');$order=OrderAccess::find($token);if(!$order)$error='کد پیگیری معتبر نیست.';}
  $this->pageTitle='پیگیری سفارش';$this->render('index',array('order'=>$order,'error'=>$error,'token'=>$token));
 }
}

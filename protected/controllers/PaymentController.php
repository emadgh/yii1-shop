<?php
class PaymentController extends AppController {
 public function actionStart($number){
  if(!Yii::app()->request->isPostRequest)throw new CHttpException(405);
  $order=Order::model()->findByAttributes(array('order_number'=>$number));$access=OrderAccess::find(Yii::app()->request->getPost('token',''));if(!$order||(Yii::app()->session['last_order']!==$number&&(!$access||$access->id!==$order->id)))throw new CHttpException(403);
  try{$this->redirect(Zarinpal::start($order->id));}catch(Exception $e){Yii::app()->user->setFlash('error',$e->getMessage());$this->redirect(array('cart/success','number'=>$number));}
 }
 public function actionCallback(){
  header('Cache-Control: no-store');try{$p=Zarinpal::verify(Yii::app()->request->getQuery('Authority'));$message=$p['state']==='paid'?'پرداخت تأیید شد. سفارش شما ثبت و پرداخت شده است.':'مبلغ دریافت شده اما سفارش نیاز به بررسی مدیر دارد؛ پرداخت را تکرار نکنید.';}catch(Exception $e){$message=$e->getMessage();}
  $this->pageTitle='نتیجه پرداخت';$this->renderText('<section class="panel"><h1>نتیجه پرداخت</h1><p>'.CHtml::encode($message).'</p>'.CHtml::link('فروشگاه',array('shop/index')).'</section>');
 }
}

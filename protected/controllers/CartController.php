<?php
class CartController extends AppController {
 protected function beforeAction($action){Commerce::requireEnabled();return parent::beforeAction($action);}
 public function actionIndex() { $this->pageTitle='سبد خرید'; $this->render('index',array('summary'=>Yii::app()->cart->summary())); }
 public function actionAdd(){$this->mutateCart(true);}
 public function actionUpdate(){$this->mutateCart(false);}
 public function actionSummary(){$this->cartResponse();}
 private function mutateCart($add){
  $this->requirePost();try{$method=$add?'add':'setQuantity';Yii::app()->cart->$method(Yii::app()->request->getPost('product_id'),Yii::app()->request->getPost('remove')==='1'?0:Yii::app()->request->getPost('quantity',$add?1:0));}
  catch(CHttpException $e){if(!Yii::app()->request->isAjaxRequest)throw $e;$this->cartResponse($e->getMessage(),$e->statusCode);}
  if(Yii::app()->request->isAjaxRequest)$this->cartResponse();
  Yii::app()->user->setFlash('success','سبد خرید به‌روز شد.');$this->redirect(array('cart/index'));
 }
 private function cartResponse($error=null,$status=200){
  $summary=Yii::app()->cart->summary();header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');http_response_code($status);
  echo CJSON::encode(array('ok'=>$error===null,'error'=>$error,'count'=>$summary['count'],'checkout_lines'=>$this->widget('CheckoutLines',array('summary'=>$summary),true),'mini'=>$this->widget('CartContents',array('summary'=>$summary,'compact'=>true),true),'page'=>$this->widget('CartContents',array('summary'=>$summary),true)));Yii::app()->end();
 }
 public function actionCheckout() {
   $summary=Yii::app()->cart->summary(); if(!$summary['items'])$this->redirect(array('cart/index'));
   $form=new CheckoutForm; $coupon=trim((string)Yii::app()->request->getPost('coupon',''));$discount=0;
   if(isset($_POST['CheckoutForm'])) {
     $form->attributes=$_POST['CheckoutForm'];
     if($form->validate()) {
       try { $number=OrderService::reserve(Yii::app()->cart->items(),$form->attributes,$coupon); Yii::app()->cart->clear(); Yii::app()->session['last_order']=$number;$placed=Order::model()->findByAttributes(array('order_number'=>$number));Yii::app()->session['order_tracking_token']=OrderAccess::issue($placed->id); $this->redirect(array('cart/success','number'=>$number)); }
       catch(Exception $e) { $form->addError('shipping_address',$e->getMessage()); }
     }
   }
   if($coupon) { try { $row=Yii::app()->db->createCommand('SELECT * FROM coupons WHERE code=:c')->queryRow(true,array(':c'=>strtoupper($coupon)));$discount=Coupon::discount($row,$summary['total']); } catch(Exception $e) { $form->addError('notes',$e->getMessage()); } }
   $this->pageTitle='ثبت سفارش';$this->render('checkout',array('formModel'=>$form,'summary'=>$summary,'coupon'=>$coupon,'discount'=>$discount));
 }
 public function actionShipping(){
   $this->requirePost();header('Content-Type: application/json; charset=utf-8');try{$summary=Yii::app()->cart->summary();$method=Yii::app()->request->getPost('method','standard');$fee=Shipping::quote(is_string($method)?$method:'',$summary['total']);$discount=0;$code=Yii::app()->request->getPost('code','');if(!is_string($code))throw new Exception('کد معتبر نیست.');if($code!==''){$row=Yii::app()->db->createCommand('SELECT * FROM coupons WHERE code=:code')->queryRow(true,array(':code'=>strtoupper(trim($code))));$discount=Coupon::discount($row,$summary['total']);}echo CJSON::encode(array('ok'=>true,'shipping'=>Money::format($fee),'total'=>Money::format($summary['total']-$discount+$fee)));}catch(Exception $e){http_response_code(422);echo CJSON::encode(array('ok'=>false,'error'=>$e->getMessage()));}Yii::app()->end();
 }
 public function actionCoupon() {
   $this->requirePost();header('Content-Type: application/json; charset=utf-8');
   try { $summary=Yii::app()->cart->summary();$code=strtoupper(trim((string)Yii::app()->request->getPost('code','')));$row=Yii::app()->db->createCommand('SELECT * FROM coupons WHERE code=:c')->queryRow(true,array(':c'=>$code));$discount=Coupon::discount($row,$summary['total']);echo CJSON::encode(array('ok'=>true,'discount'=>Money::format($discount),'total'=>Money::format($summary['total']-$discount))); }
   catch(Exception $e) { http_response_code(422);echo CJSON::encode(array('ok'=>false,'error'=>$e->getMessage())); } Yii::app()->end();
 }
 public function actionSuccess($number) { header('Cache-Control: no-store');header('Referrer-Policy: no-referrer');
   if(!preg_match('/^ORD-[A-F0-9]{12}$/D',$number))$this->notFound();
   $order=Yii::app()->session['last_order']===$number?Order::model()->findByAttributes(array('order_number'=>$number)):null;
   $this->pageTitle='سفارش ثبت شد';$this->render('success',array('number'=>$number,'order'=>$order));
 }
 protected function requirePost() { if(!Yii::app()->request->isPostRequest)throw new CHttpException(405); }
}

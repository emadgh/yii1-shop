<?php
class CartController extends AppController {
 protected function beforeAction($action){Commerce::requireEnabled();return parent::beforeAction($action);}
 public function actionIndex() { $this->pageTitle='سبد خرید'; $this->render('index',array('summary'=>Yii::app()->cart->summary())); }
 public function actionAdd() { $this->requirePost(); Yii::app()->cart->add(Yii::app()->request->getPost('product_id'),Yii::app()->request->getPost('quantity',1)); Yii::app()->user->setFlash('success','محصول به سبد خرید اضافه شد.');$this->redirect(array('cart/index')); }
 public function actionUpdate() { $this->requirePost();Yii::app()->cart->setQuantity(Yii::app()->request->getPost('product_id'),Yii::app()->request->getPost('quantity',0));$this->redirect(array('cart/index')); }
 public function actionCheckout() {
   $summary=Yii::app()->cart->summary(); if(!$summary['items'])$this->redirect(array('cart/index'));
   $form=new CheckoutForm; $coupon=trim((string)Yii::app()->request->getPost('coupon',''));$discount=0;
   if(isset($_POST['CheckoutForm'])) {
     $form->attributes=$_POST['CheckoutForm'];
     if($form->validate()) {
       try { $number=OrderService::reserve(Yii::app()->cart->items(),$form->attributes,$coupon); Yii::app()->cart->clear(); Yii::app()->session['last_order']=$number; $this->redirect(array('cart/success','number'=>$number)); }
       catch(Exception $e) { $form->addError('shipping_address',$e->getMessage()); }
     }
   }
   if($coupon) { try { $row=Yii::app()->db->createCommand('SELECT * FROM coupons WHERE code=:c')->queryRow(true,array(':c'=>strtoupper($coupon)));$discount=Coupon::discount($row,$summary['total']); } catch(Exception $e) { $form->addError('notes',$e->getMessage()); } }
   $this->pageTitle='ثبت سفارش';$this->render('checkout',array('formModel'=>$form,'summary'=>$summary,'coupon'=>$coupon,'discount'=>$discount));
 }
 public function actionCoupon() {
   $this->requirePost();header('Content-Type: application/json; charset=utf-8');
   try { $summary=Yii::app()->cart->summary();$code=strtoupper(trim((string)Yii::app()->request->getPost('code','')));$row=Yii::app()->db->createCommand('SELECT * FROM coupons WHERE code=:c')->queryRow(true,array(':c'=>$code));$discount=Coupon::discount($row,$summary['total']);echo CJSON::encode(array('ok'=>true,'discount'=>Money::format($discount),'total'=>Money::format($summary['total']-$discount))); }
   catch(Exception $e) { http_response_code(422);echo CJSON::encode(array('ok'=>false,'error'=>$e->getMessage())); } Yii::app()->end();
 }
 public function actionSuccess($number) {
   if(!preg_match('/^ORD-[A-F0-9]{12}$/D',$number))$this->notFound();
   $order=Yii::app()->session['last_order']===$number?Order::model()->findByAttributes(array('order_number'=>$number)):null;
   $this->pageTitle='سفارش ثبت شد';$this->render('success',array('number'=>$number,'order'=>$order));
 }
 protected function requirePost() { if(!Yii::app()->request->isPostRequest)throw new CHttpException(405); }
}

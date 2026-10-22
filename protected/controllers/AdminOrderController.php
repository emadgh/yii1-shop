<?php
class AdminOrderController extends AdminController {
 public function actionIndex() {
   $criteria=new CDbCriteria; $status=(string)Yii::app()->request->getQuery('status','');
   if($status)$criteria->compare('t.status',$status);$criteria->order='t.id DESC';
   $this->pageTitle='سفارش‌ها';$this->render('//admin/orders/index',array('status'=>$status,'provider'=>new CActiveDataProvider('Order',array('criteria'=>$criteria,'pagination'=>array('pageSize'=>20)))));
 }
 public function actionView($id) { $order=$this->load($id);$this->pageTitle='سفارش '.$order->order_number;$this->render('//admin/orders/view',array('order'=>$order)); }
 public function actionStatus($id) {
   $this->requirePost();try { OrderService::changeStatus((int)$id,(string)Yii::app()->request->getPost('status'));$this->flash('وضعیت سفارش و گردش مالی و انبار ثبت شد.'); }
   catch(Exception $e) { $this->flash($e->getMessage(),'error'); } $this->redirect(array('adminOrder/view','id'=>$id));
 }
 private function load($id) { $m=Order::model()->with('items')->findByPk($id);if(!$m)$this->notFound();return $m; }
}

<?php
class AdminCouponController extends AdminController {
 public function actionIndex() { $this->pageTitle='کدهای تخفیف';$this->render('//admin/coupons/index'); }
 public function actionCreate() { $this->edit(new Coupon); }
 public function actionUpdate($id) { $m=Coupon::model()->findByPk($id);if(!$m)$this->notFound();$this->edit($m); }
 private function edit($model) {
   $error='';if($model->isNewRecord){$model->kind='percent';$model->is_active=1;$model->amount=10;$model->minimum_total=0;$model->maximum_discount=0;$model->usage_limit=0;}
   if(isset($_POST['Coupon']) && is_array($_POST['Coupon'])) {
     try { $data=$_POST['Coupon'];$data['code']=strtoupper(trim(isset($data['code'])?$data['code']:''));foreach(array('minimum_total','maximum_discount') as $k)$data[$k]=Money::parse(isset($data[$k])?$data[$k]:'0');if(isset($data['kind']) && $data['kind']==='fixed')$data['amount']=Money::parse($data['amount']);
       foreach(array('starts_at','expires_at') as $k)$data[$k]=empty($data[$k])?null:str_replace('T',' ',$data[$k]).(strlen($data[$k])===16?':00':'');
       $model->attributes=$data;$model->validateRules();$model->save(false);$this->flash('کد تخفیف ذخیره شد.');$this->redirect(array('adminCoupon/index'));
     } catch(Exception $e){$error=$e->getMessage();}
   }
   $this->pageTitle='ویرایش کد تخفیف';$this->render('//admin/coupons/form',array('model'=>$model,'error'=>$error));
 }
 public function actionToggle($id) { $this->requirePost();$m=Coupon::model()->findByPk($id);if(!$m)$this->notFound();$m->is_active=$m->is_active?0:1;$m->save(false);$this->redirect(array('adminCoupon/index')); }
}

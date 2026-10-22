<?php
class AdminCollectionController extends AdminController {
 public function actionIndex(){$this->pageTitle='کالکشن‌های محصولات';$this->render('//admin/collections/index',array('rows'=>ProductCollection::model()->findAll(array('order'=>'id DESC'))));}
 public function actionCreate(){$this->edit(new ProductCollection);}
 public function actionUpdate($id){$this->edit($this->load($id));}
 private function load($id){$m=ProductCollection::model()->findByPk($id);if(!$m)$this->notFound();return $m;}
 private function edit($model){
  $error='';$selected=$model->isNewRecord?array():$model->productIds();
  if(isset($_POST['ProductCollection'])){try{
   $model->attributes=$_POST['ProductCollection'];CmsMedia::bind($model,Yii::app()->request->getPost('image_id',''));
   $raw=Yii::app()->request->getPost('products',array());if(!is_array($raw) || count($raw)>500)throw new Exception('حداکثر ۵۰۰ محصول قابل انتخاب است.');
   $positions=Yii::app()->request->getPost('positions',array());if(!is_array($positions))throw new Exception('ترتیب نامعتبر است.');$selected=array_values(array_unique(array_map('intval',$raw)));
   foreach($selected as $id)if(!Product::model()->exists('id=:id',array(':id'=>$id)))throw new Exception('محصول نامعتبر است.');
   usort($selected,function($a,$b)use($positions){return ((int)($positions[$a]??0)<=> (int)($positions[$b]??0))?:($a<=>$b);});
   $db=Yii::app()->db;$tx=$db->beginTransaction();try{if(!$model->save())throw new Exception('اطلاعات کالکشن را اصلاح کنید.');$db->createCommand()->delete('collection_products','collection_id=:id',array(':id'=>$model->id));foreach($selected as $position=>$id)$db->createCommand()->insert('collection_products',array('collection_id'=>$model->id,'product_id'=>$id,'position'=>$position));$tx->commit();$this->flash('کالکشن ذخیره شد.');$this->redirect(array('adminCollection/index'));}catch(Exception $e){if($tx->active)$tx->rollback();throw $e;}
  }catch(Exception $e){$error=$e->getMessage();}}
  $this->pageTitle='ویرایش کالکشن';$this->render('//admin/collections/form',array('model'=>$model,'selected'=>$selected,'error'=>$error,'products'=>Product::model()->findAll(array('order'=>'title'))));
 }
 public function actionDelete($id){$this->requirePost();$model=$this->load($id);$db=Yii::app()->db;$tx=$db->beginTransaction();try{$db->createCommand()->delete('collection_products','collection_id=:id',array(':id'=>$id));$db->createCommand()->update('theme_blocks',array('collection_id'=>null),'collection_id=:id',array(':id'=>$id));$model->delete();$tx->commit();}catch(Exception $e){$tx->rollback();throw $e;}$this->redirect(array('adminCollection/index'));}
}

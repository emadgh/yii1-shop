<?php
class AdminProductController extends AdminController {
 public function actionIndex() { $this->pageTitle='مدیریت محصولات';$this->render('//admin/products/index'); }
 public function actionCreate() { $m=new Product;$m->discount_type='none';$m->discount_value=0;$m->price=0;$m->compare_price=0;$m->stock=0;$this->edit($m); }
 public function actionUpdate($id) { $this->edit($this->load($id)); }
 private function edit($model) {
   $db=Yii::app()->db;$error='';$specs=array();$meta=array();$albums=ProductAlbumService::editorRows($model);$albumRevision=$model->isNewRecord?'':ProductAlbumService::revision($model->id);$tagsText=$model->isNewRecord?'':$model->tagsText();$stockSnapshot=$model->isNewRecord?0:(int)$model->stock;
   if(!$model->isNewRecord) {
     foreach($model->specifications as $s)$specs[]=array('key'=>$s->name,'value'=>$s->value);
     foreach($model->metadata as $m)$meta[]=array('key'=>$m->meta_key,'value'=>$m->meta_value);
   }
   if(isset($_POST['Product']) && is_array($_POST['Product'])) {
     $tagsText=(string)Yii::app()->request->getPost('tags','');$specs=(array)Yii::app()->request->getPost('spec_rows',array());$meta=(array)Yii::app()->request->getPost('meta_rows',array());$editAlbums=Yii::app()->request->getPost('albums_present')==='1';
     if($editAlbums){$albums=Yii::app()->request->getPost('albums',array());$albumRevision=Yii::app()->request->getPost('album_revision','');}
     try {
       $data=$_POST['Product'];foreach(array('price','compare_price') as $k)$data[$k]=Money::parse(isset($data[$k]) && $data[$k]!==''?$data[$k]:'0');
       if(isset($data['discount_type']) && $data['discount_type']==='fixed')$data['discount_value']=Money::parse($data['discount_value']);
       $model->attributes=$data;if($model->parent_product_id==='')$model->parent_product_id=null;$model->featured=!empty($data['featured'])?1:0;if($model->category_id==='')$model->category_id=null;
       $specPairs=$this->pairs($specs);$metaPairs=$this->pairs($meta);$albumData=$editAlbums?ProductAlbumService::normalize($albums):array();
       if($model->validate()) {
         $tx=$db->beginTransaction();
         try {
           $oldStock=0;
           if(!$model->isNewRecord) {
             $oldStock=(int)$db->createCommand('SELECT stock FROM products WHERE id=:id'.($db->driverName==='mysql'?' FOR UPDATE':''))->queryScalar(array(':id'=>$model->id));
             if($oldStock!==(int)Yii::app()->request->getPost('stock_snapshot',-1))throw new Exception('موجودی پس از بازکردن فرم تغییر کرده است. صفحه را تازه و دوباره ویرایش کنید.');
           }
           $isNew=$model->isNewRecord;if(!$model->save(false))throw new Exception('ذخیره ناموفق بود.');
           $delta=(int)$model->stock-$oldStock;if($delta || $isNew)OrderService::movement($model->id,null,$delta,$isNew?'opening':'adjustment','ثبت از فرم محصول');
           foreach(array('product_attributes','product_meta','product_tag') as $table)$db->createCommand()->delete($table,'product_id=:id',array(':id'=>$model->id));
           foreach($specPairs as $i=>$p)$db->createCommand()->insert('product_attributes',array('product_id'=>$model->id,'name'=>$p[0],'value'=>$p[1],'sort_order'=>$i));
           foreach($metaPairs as $p)$db->createCommand()->insert('product_meta',array('product_id'=>$model->id,'meta_key'=>$p[0],'meta_value'=>$p[1]));
           TagService::sync('product',$model->id,$tagsText);
           if($editAlbums)ProductAlbumService::save($model,$albumData,$isNew?null:$albumRevision);
           $tx->commit();$this->flash('محصول ذخیره شد.');$this->redirect(array('adminProduct/index'));
         }catch(Exception $e){if($tx->active)$tx->rollback();if(isset($isNew) && $isNew){$model->isNewRecord=true;$model->primaryKey=null;}throw $e;}
       }
     }catch(Exception $e){$error=$e->getMessage();}
   }
   $this->pageTitle=$model->isNewRecord?'محصول جدید':'ویرایش محصول';
   $this->render('//admin/products/form',array('model'=>$model,'error'=>$error,'specs'=>$specs,'meta'=>$meta,'albums'=>$albums,'albumRevision'=>$albumRevision,'tagsText'=>$tagsText,'stockSnapshot'=>$stockSnapshot,'categories'=>Category::options()));
 }
 private function pairs($rows) {
   if(count($rows)>100)throw new Exception('حداکثر ۱۰۰ مشخصه مجاز است.');$out=array();$seen=array();
   foreach($rows as $row) { $key=trim(isset($row['key'])?(string)$row['key']:'');$value=trim(isset($row['value'])?(string)$row['value']:'');if($key==='' && $value==='')continue;
     if($key==='' || mb_strlen($key)>100 || mb_strlen($value)>2000 || isset($seen[$key]))throw new Exception('کلید خالی، تکراری یا مقدار بیش از حد بلند است.');
     $seen[$key]=true;$out[]=array($key,$value);
   }return $out;
 }
 public function actionDelete($id) { $this->requirePost();$m=$this->load($id);if(Product::model()->countByAttributes(array('parent_product_id'=>$id)))throw new CHttpException(422,'ابتدا تنوع‌های محصول را جدا یا حذف کنید.');$held=Yii::app()->db->createCommand("SELECT COUNT(*) FROM order_items i JOIN orders o ON o.id=i.order_id WHERE i.product_id=:id AND o.stock_held=1 AND o.status NOT IN ('completed','shipped','paid')")->queryScalar(array(':id'=>$id));if($held)$this->flash('محصول رزرو فعال دارد؛ ابتدا سفارش را تعیین تکلیف کنید.','error');else{Yii::app()->db->createCommand()->delete('collection_products','product_id=:id',array(':id'=>$id));$m->delete();$this->flash('محصول حذف شد؛ رسانه‌ها در کتابخانه باقی ماندند.');}$this->redirect(array('adminProduct/index')); }
 private function load($id){$m=Product::model()->findByPk($id);if(!$m)$this->notFound();return $m;}
}

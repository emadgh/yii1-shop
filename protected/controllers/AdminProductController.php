<?php
class AdminProductController extends AdminController {
 public function actionIndex() { $this->pageTitle='مدیریت محصولات';$this->render('//admin/products/index'); }
 public function actionCreate() { $m=new Product;$m->discount_type='none';$m->discount_value=0;$m->price=0;$m->compare_price=0;$m->stock=0;$this->edit($m); }
 public function actionUpdate($id) { $this->edit($this->load($id)); }
 private function edit($model) {
   $db=Yii::app()->db;$error='';$specs=array();$meta=array();$selected=array();$tagsText=$model->isNewRecord?'':$model->tagsText();$stockSnapshot=$model->isNewRecord?0:(int)$model->stock;
   if(!$model->isNewRecord) {
     foreach($model->specifications as $s)$specs[]=array('key'=>$s->name,'value'=>$s->value);
     foreach($model->metadata as $m)$meta[]=array('key'=>$m->meta_key,'value'=>$m->meta_value);
     foreach($model->images as $img){$id=$db->createCommand('SELECT id FROM media WHERE filename=:p')->queryScalar(array(':p'=>$img->filename));if($id)$selected[]=$id;}
   }
   if(isset($_POST['Product']) && is_array($_POST['Product'])) {
     $tagsText=(string)Yii::app()->request->getPost('tags','');$specs=(array)Yii::app()->request->getPost('spec_rows',array());$meta=(array)Yii::app()->request->getPost('meta_rows',array());$selected=(string)Yii::app()->request->getPost('media_ids','');
     try {
       $data=$_POST['Product'];foreach(array('price','compare_price') as $k)$data[$k]=Money::parse(isset($data[$k]) && $data[$k]!==''?$data[$k]:'0');
       if(isset($data['discount_type']) && $data['discount_type']==='fixed')$data['discount_value']=Money::parse($data['discount_value']);
       $model->attributes=$data;if($model->parent_product_id==='')$model->parent_product_id=null;$model->featured=!empty($data['featured'])?1:0;if($model->category_id==='')$model->category_id=null;
       $specPairs=$this->pairs($specs);$metaPairs=$this->pairs($meta);$media=MediaService::selected($selected,true);
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
           foreach(array('product_attributes','product_meta','product_tag','product_images') as $table)$db->createCommand()->delete($table,'product_id=:id',array(':id'=>$model->id));
           foreach($specPairs as $i=>$p)$db->createCommand()->insert('product_attributes',array('product_id'=>$model->id,'name'=>$p[0],'value'=>$p[1],'sort_order'=>$i));
           foreach($metaPairs as $p)$db->createCommand()->insert('product_meta',array('product_id'=>$model->id,'meta_key'=>$p[0],'meta_value'=>$p[1]));
           TagService::sync('product',$model->id,$tagsText);
           foreach($media as $i=>$row)$db->createCommand()->insert('product_images',array('product_id'=>$model->id,'filename'=>$row['filename'],'alt_text'=>$model->title,'sort_order'=>$i));
           $tx->commit();$this->flash('محصول ذخیره شد.');$this->redirect(array('adminProduct/index'));
         }catch(Exception $e){if($tx->active)$tx->rollback();if(isset($isNew) && $isNew){$model->isNewRecord=true;$model->primaryKey=null;}throw $e;}
       }
     }catch(Exception $e){$error=$e->getMessage();}
   }
   $this->pageTitle=$model->isNewRecord?'محصول جدید':'ویرایش محصول';
   $this->render('//admin/products/form',array('model'=>$model,'error'=>$error,'specs'=>$specs,'meta'=>$meta,'selected'=>$selected,'tagsText'=>$tagsText,'stockSnapshot'=>$stockSnapshot,'categories'=>Category::options()));
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

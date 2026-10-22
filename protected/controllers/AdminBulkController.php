<?php
class AdminBulkController extends AdminController {
 public function actionExport(){
  header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="products.csv"');header('Cache-Control: no-store');$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,array('sku','title','price_toman','stock'),',','"','');
  foreach(Product::model()->findAll(array('order'=>'id')) as $p){$safe=function($v){return preg_match('/^[=+@\-\t\r]/u',(string)$v)?"'".$v:$v;};fputcsv($out,array($safe($p->sku),$safe($p->title),$p->price,$p->stock),',','"','');}fclose($out);Yii::app()->end();
 }
 public function actionImport(){
  $this->requirePost();$tx=null;try{$upload=CUploadedFile::getInstanceByName('file');if(!$upload||$upload->size>1048576)throw new Exception('فایل CSV حداکثر یک مگابایت مجاز است.');$h=fopen($upload->tempName,'r');$header=fgetcsv($h,4096,',','"','');if($header)$header[0]=ltrim($header[0],"\xEF\xBB\xBF");if($header!==array('sku','title','price_toman','stock'))throw new Exception('از قالب خروجی محصولات استفاده کنید.');$rows=array();while(($row=fgetcsv($h,4096,',','"',''))!==false){if(count($rows)>=500||count($row)!==4)throw new Exception('حداکثر ۵۰۰ ردیف با چهار ستون مجاز است.');if(!ctype_digit($row[2])||!ctype_digit($row[3])||(int)$row[2]>1000000000000||(int)$row[3]>1000000)throw new Exception('قیمت تومانی یا موجودی معتبر نیست.');$rows[]=$row;}fclose($h);
   $db=Yii::app()->db;$tx=$db->beginTransaction();$seen=array();foreach($rows as $r){if(isset($seen[$r[0]]))throw new Exception('SKU تکراری است.');$seen[$r[0]]=true;$p=Product::model()->findByAttributes(array('sku'=>$r[0]));if(!$p)throw new Exception('SKU پیدا نشد: '.$r[0]);$old=(int)$db->createCommand('SELECT stock FROM products WHERE id=:id'.($db->driverName==='mysql'?' FOR UPDATE':''))->queryScalar(array(':id'=>$p->id));$p->price=$r[2];$p->stock=$r[3];if(!$p->save())throw new Exception('اطلاعات محصول معتبر نیست: '.$r[0]);if((int)$p->stock!==$old)OrderService::movement($p->id,null,(int)$p->stock-$old,'adjustment','ورود گروهی CSV');}$tx->commit();$this->flash(count($rows).' محصول بروزرسانی شد.');
  }catch(Exception $e){if($tx&&$tx->active)$tx->rollback();$this->flash($e->getMessage(),'error');}$this->redirect(array('adminProduct/index'));
 }
}

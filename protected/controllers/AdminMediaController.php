<?php
class AdminMediaController extends AdminController {
 private function json($value,$status=200) { http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo CJSON::encode($value);Yii::app()->end(); }
 public function actionIndex() { $this->pageTitle='کتابخانه رسانه';$this->render('//admin/media/index'); }
 public function actionList() {
   $q=mb_substr(trim((string)Yii::app()->request->getQuery('q','')),0,100);$offset=max(0,(int)Yii::app()->request->getQuery('offset',0));
   $rows=MediaRepository::library($q,$offset,(string)Yii::app()->request->getQuery('accept','*'),(string)Yii::app()->request->getQuery('scope','all'));$this->json(array('items'=>array_map(array('MediaService','describe'),$rows),'hasMore'=>count($rows)===48));
 }
 public function actionUpload() {
   $this->requirePost();try { $file=CUploadedFile::getInstanceByName('file');if(!$file || $file->hasError)throw new Exception('فایل دریافت نشد.');$row=MediaService::ingest($file->tempName,$file->name,Yii::app()->request->getPost('scope','general'),Yii::app()->request->getPost('accept','*'));$this->json(array('ok'=>true,'item'=>MediaService::describe($row))); }catch(Exception $e){$this->json(array('ok'=>false,'error'=>$e->getMessage()),422);}
 }
 public function actionDelete($id) {
   $this->requirePost();try { $row=MediaService::find((int)$id);if(!$row)throw new Exception('فایل پیدا نشد.');
     $db=Yii::app()->db;$tx=$db->beginTransaction();try {
       $stem=preg_replace('~-(thumb|medium|large)\.webp$~','',$row['filename']);$pattern='%'.$stem.'%';
       $refs=(int)$db->createCommand('SELECT COUNT(*) FROM product_images WHERE filename=:p')->queryScalar(array(':p'=>$row['filename']))+(int)$db->createCommand('SELECT COUNT(*) FROM posts WHERE cover_image=:p OR body LIKE :body OR excerpt LIKE :excerpt')->queryScalar(array(':p'=>$row['filename'],':body'=>$pattern,':excerpt'=>$pattern))+(int)$db->createCommand('SELECT COUNT(*) FROM products WHERE description LIKE :description OR summary LIKE :summary')->queryScalar(array(':description'=>$pattern,':summary'=>$pattern));
       foreach(array('categories','collections') as $table)$refs+=(int)$db->createCommand("SELECT COUNT(*) FROM $table WHERE image=:file OR description LIKE :pattern")->queryScalar(array(':file'=>$row['filename'],':pattern'=>$pattern));
       $refs+=(int)$db->createCommand('SELECT COUNT(*) FROM company_catalogs WHERE image_id=:image OR pdf_id=:pdf OR description LIKE :pattern')->queryScalar(array(':image'=>$row['id'],':pdf'=>$row['id'],':pattern'=>$pattern));
       foreach(ThemeService::catalog() as $key=>$theme)if((int)ThemeOptions::get($key)['hero_media']===(int)$row['id'])$refs++;
       if($refs)throw new Exception('این رسانه در محتوا استفاده شده است؛ ابتدا اتصال آن را حذف کنید.');
       $db->createCommand()->delete('media','id=:id',array(':id'=>$id));$tx->commit();
     }catch(Exception $e){if($tx->active)$tx->rollback();throw $e;}
     MediaService::removeFiles($row['filename'],json_decode($row['variants'],true)?:array());$this->json(array('ok'=>true));
   }catch(Exception $e){$this->json(array('ok'=>false,'error'=>$e->getMessage()),422);}
 }
 public function actionPreview() { $this->requirePost();$text=Yii::app()->request->getPost('text','');if(strlen($text)>200000)$this->json(array('error'=>'متن بیش از حد بلند است.'),422);$this->json(array('html'=>Content::markdown($text))); }
}

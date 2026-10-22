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
   $this->requirePost();try{MediaDeleteService::delete((int)$id);$this->json(array('ok'=>true));}catch(Exception $e){$this->json(array('ok'=>false,'error'=>$e->getMessage()),422);}
 }
 public function actionPreview() { $this->requirePost();$text=Yii::app()->request->getPost('text','');if(strlen($text)>200000)$this->json(array('error'=>'متن بیش از حد بلند است.'),422);$this->json(array('html'=>Content::markdown($text))); }
}

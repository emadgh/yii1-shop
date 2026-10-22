<?php
class CatalogController extends AppController
{
    public function actionIndex()
    {
        $this->pageTitle='کاتالوگ‌های شرکت';
        $this->metaDescription='معرفی و دانلود کاتالوگ‌های شرکت';
        $rows=CompanyCatalogQuery::published();
        MediaRepository::primeFilenames(array_filter(array_column($rows,'image_filename')));
        $provider=new CArrayDataProvider($rows,array('pagination'=>array('pageSize'=>12),'sort'=>false));
        $this->render('index',array('provider'=>$provider));
    }
    public function actionDownload($id)
    {
        $row=CompanyCatalogQuery::download($id);
        if (!$row || !UploadService::validPath($row['filename'])) $this->notFound();
        $path=Yii::app()->params['uploadsDir'].'/'.$row['filename'];
        if (!is_file($path)) $this->notFound();
        Yii::app()->request->sendFile('catalog-'.(int)$id.'.pdf',file_get_contents($path),'application/pdf');
    }
}

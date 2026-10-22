<?php
class ShopController extends AppController
{
    public function actionIndex($category=null,$tag=null,$q=null,$sort='new',$facet=array(),$min=null,$max=null) {
      $this->pageTitle='فروشگاه';
      $this->render('index',CatalogSearchQuery::search($category,$tag,$q,$sort,$facet,$min,$max));
    }
    public function actionSuggest($q=''){
      header('Content-Type: application/json; charset=utf-8');
      echo CJSON::encode(array('items'=>CatalogSearchQuery::suggestions($q)));
      Yii::app()->end();
    }
    public function actionCategory($slug) { $this->actionIndex($slug); }
    public function actionView($slug) {
      $data=CatalogQuery::detail($slug);$p=$data['product'];
      if(!$p) $this->notFound();
      $this->socialImage=$p->cover();
      $this->pageTitle=$p->seo_title ? $p->seo_title : $p->title;
      $this->metaDescription=mb_substr($p->seo_description ? $p->seo_description : Content::plain($p->summary),0,160);
      $this->render('view',$data);
    }
}

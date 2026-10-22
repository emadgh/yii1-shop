<?php
class ShopController extends AppController
{
    public function actionIndex($category=null,$tag=null,$q=null,$sort='new') {
      $criteria=new CDbCriteria;
      $criteria->compare('t.status','published');
      $criteria->with=array('images'); $criteria->together=false;
      if($category) {
         $cat=Category::model()->findByAttributes(array('slug'=>$category));
         if(!$cat) $this->notFound();
         $criteria->addInCondition('t.category_id',Hierarchy::descendants(Hierarchy::rows('categories'),$cat->id));
      }
      if($q) SearchService::products($criteria,$q);
      if($tag) {
         $found=Tag::model()->findByAttributes(array('slug'=>$tag));
         if(!$found) $this->notFound();
         $criteria->addCondition('EXISTS (SELECT 1 FROM product_tag pt WHERE pt.product_id=t.id AND pt.tag_id=:tagId)');
         $criteria->params[':tagId']=$found->id;
      }
      $sale="(t.price - CASE WHEN t.discount_type='percent' THEN CAST(t.price*t.discount_value/100 AS INTEGER) WHEN t.discount_type='fixed' THEN t.discount_value ELSE 0 END)";
      if(Yii::app()->db->driverName==='mysql')$sale=str_replace('CAST(t.price*t.discount_value/100 AS INTEGER)','FLOOR(t.price*t.discount_value/100)',$sale);
      $orders=array('new'=>'t.created_at DESC','price-asc'=>$sale.' ASC','price-desc'=>$sale.' DESC');
      $criteria->order=isset($orders[$sort])?$orders[$sort]:$orders['new'];
      $provider=new CActiveDataProvider('Product',array('criteria'=>$criteria,'pagination'=>array('pageSize'=>12)));
      $this->pageTitle='فروشگاه';
      $this->render('index',array('provider'=>$provider,'categories'=>Category::model()->findAll(array('order'=>'name')),
        'category'=>$category,'q'=>$q,'sort'=>$sort,'tag'=>$tag));
    }
    public function actionCategory($slug) { $this->actionIndex($slug); }
    public function actionView($slug) {
      $p=Product::model()->findByAttributes(array('slug'=>$slug,'status'=>'published'));
      if(!$p) $this->notFound();
      $this->pageTitle=$p->seo_title ? $p->seo_title : $p->title;
      $this->metaDescription=mb_substr($p->seo_description ? $p->seo_description : Content::plain($p->summary),0,160);
      $this->render('view',array('product'=>$p));
    }
}

<?php
class ShopController extends AppController
{
    public function actionIndex($category=null,$tag=null,$q=null,$sort='new',$facet=array(),$min=null,$max=null) {
      $criteria=new CDbCriteria;
      $criteria->compare('t.status','published');$criteria->addCondition('t.parent_product_id IS NULL');
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
      $facetOptions=array();$fc=new CDbCriteria;$fc->condition="p.status='published'";if(isset($cat))$fc->addInCondition('p.category_id',Hierarchy::descendants(Hierarchy::rows('categories'),$cat->id));
      $rows=Yii::app()->db->createCommand()->selectDistinct('a.name,a.value')->from('product_attributes a')->join('products p','p.id=a.product_id')->where($fc->condition,$fc->params)->order('a.name,a.value')->limit(200)->queryAll();foreach($rows as $row)if(mb_strlen($row['value'])<100)$facetOptions[$row['name']][$row['value']]=$row['value'];
      if(!is_array($facet)||count($facet)>10)throw new CHttpException(400);foreach($facet as $name=>$value){if($value==='')continue;if(!is_string($value)||!isset($facetOptions[$name][$value]))throw new CHttpException(400,'فیلتر معتبر نیست.');$i=count($criteria->params);$criteria->addCondition('EXISTS (SELECT 1 FROM product_attributes af WHERE af.product_id=t.id AND af.name=:fn'.$i.' AND af.value=:fv'.$i.')');$criteria->params[':fn'.$i]=$name;$criteria->params[':fv'.$i]=$value;}
      foreach(array('min'=>'>=','max'=>'<=') as $key=>$operator){$value=$$key;if($value===null||$value==='')continue;try{$number=Money::parse($value);}catch(Exception $e){throw new CHttpException(400,'بازه قیمت معتبر نیست.');}$criteria->addCondition($sale.' '.$operator.' :price_'.$key);$criteria->params[':price_'.$key]=$number;}
      $provider=new CActiveDataProvider('Product',array('criteria'=>$criteria,'pagination'=>array('pageSize'=>12)));
      $this->pageTitle='فروشگاه';
      $this->render('index',array('provider'=>$provider,'categories'=>Category::model()->findAll(array('order'=>'name')),
        'facetOptions'=>$facetOptions,'facet'=>$facet,'min'=>$min,'max'=>$max,'category'=>$category,'q'=>$q,'sort'=>$sort,'tag'=>$tag));
    }
    public function actionSuggest($q=''){
      header('Content-Type: application/json; charset=utf-8');if(!is_string($q)||mb_strlen(trim($q))<2){echo CJSON::encode(array('items'=>array()));Yii::app()->end();}$c=new CDbCriteria;$c->compare('t.status','published');$c->addCondition('t.parent_product_id IS NULL');SearchService::products($c,$q);$c->limit=6;$c->order='t.title';$items=array();foreach(Product::model()->findAll($c) as $p)$items[]=array('title'=>$p->title,'url'=>$p->link(),'price'=>Commerce::showPrices()?Money::format($p->salePrice()):'');echo CJSON::encode(array('items'=>$items));Yii::app()->end();
    }
    public function actionCategory($slug) { $this->actionIndex($slug); }
    public function actionView($slug) {
      $p=Product::model()->findByAttributes(array('slug'=>$slug,'status'=>'published'));
      if(!$p) $this->notFound();
      $this->socialImage=$p->cover();
      $this->pageTitle=$p->seo_title ? $p->seo_title : $p->title;
      $this->metaDescription=mb_substr($p->seo_description ? $p->seo_description : Content::plain($p->summary),0,160);
      $this->render('view',array('product'=>$p));
    }
}

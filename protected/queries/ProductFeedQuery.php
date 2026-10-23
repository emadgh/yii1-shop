<?php
/** Bounded product feeds; widgets only render the prepared records. */
class ProductFeedQuery
{
    public static function products($type, $limit, $categoryId=null)
    {
        if(!in_array($type,array('latest_products','popular_products','category_products'),true))throw new InvalidArgumentException('Invalid product feed');
        $limit=max(1,min(24,(int)$limit));
        $categoryId=(int)$categoryId;
        $prefix=Yii::app()->baseUrl.'/product/';
        // Visits refresh within five minutes without invalidating every cached feed per beacon.
        $bucket=$type==='popular_products'?(int)floor(time()/300):0;
        $ids=SiteCache::read(array('home-product-feed',$type,$limit,$categoryId,$prefix,$bucket),array('products','categories'),function()use($type,$limit,$categoryId,$prefix){
            $db=Yii::app()->db;
            if($type==='popular_products'){
                $path=$db->driverName==='mysql'?'CONCAT(:prefix,p.slug)':':prefix || p.slug';
                return $db->createCommand("SELECT p.id FROM products p INNER JOIN (SELECT path,COUNT(*) views FROM page_visits GROUP BY path) v ON v.path=$path WHERE p.status='published' AND p.parent_product_id IS NULL ORDER BY v.views DESC,p.created_at DESC,p.id DESC LIMIT ".$limit)->queryColumn(array(':prefix'=>$prefix));
            }
            $criteria=new CDbCriteria;
            $criteria->select='t.id';
            $criteria->condition="t.status='published' AND t.parent_product_id IS NULL";
            $criteria->order='t.created_at DESC,t.id DESC';
            $criteria->limit=$limit;
            if($type==='category_products'){
                if(!$categoryId||!Category::model()->exists('id=:id',array(':id'=>$categoryId)))return array();
                $criteria->addInCondition('t.category_id',Hierarchy::descendants(Hierarchy::rows('categories'),$categoryId));
            }
            return array_map(function($product){return (int)$product->id;},Product::model()->findAll($criteria));
        });
        if(!$ids)return array();
        $criteria=new CDbCriteria;
        $criteria->addInCondition('t.id',$ids);
        $criteria->compare('t.status','published');
        $criteria->addCondition('t.parent_product_id IS NULL');
        $rows=Product::model()->with('images','category')->findAll($criteria);
        $byId=array();foreach($rows as $row)$byId[$row->id]=$row;
        $products=array();foreach($ids as $id)if(isset($byId[$id]))$products[]=$byId[$id];
        CatalogQuery::primeProducts($products);
        return $products;
    }
}

<?php
/** Theme sections are data, dispatched through a closed list of Yii widgets/views. */
class HomePage extends CWidget {
 public function run(){
  $options=ThemeOptions::get();foreach(ThemeOptions::blocks() as $block){if(!$block['is_enabled'])continue;
   switch($block['block_type']){
    case 'hero':$this->render('hero',array('options'=>$options));break;
    case 'categories':$this->widget('CategoryTiles');break;
    case 'collection':$collection=$block['collection_id']?ProductCollection::model()->findByAttributes(array('id'=>$block['collection_id'],'is_active'=>1)):null;if($block['collection_id'] && !$collection)break;$products=$collection?$collection->products($block['item_limit']):Product::model()->with('images','category')->findAll(array('condition'=>"t.status='published' AND t.featured=1",'order'=>'t.id DESC','limit'=>(int)$block['item_limit']));$this->render('collection-block',array('block'=>$block,'collection'=>$collection,'products'=>$products));break;
    case 'blog':$posts=Post::model()->findAll(array('condition'=>"status='published'",'order'=>'created_at DESC','limit'=>min(6,(int)$block['item_limit'])));$this->render('blog-block',array('posts'=>$posts,'title'=>$block['title']?:'از مجله بازارچه'));break;
    case 'banner':echo '<section class="editorial-note"><span>BAZAARCHE / EVERYDAY OBJECTS</span><h2>'.CHtml::encode($block['title']?:'جزئیات کوچک، تجربه‌ای متفاوت').'</h2><p>'.CHtml::encode(ShopSettings::get('site_description')).'</p></section>';break;
   }
  }
 }
}

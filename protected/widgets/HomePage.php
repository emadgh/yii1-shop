<?php
/** Theme sections are data, dispatched through a closed list of Yii widgets/views. */
class HomePage extends CWidget {
 public $sections=array();
 public function run(){
  foreach($this->sections as $data){$block=$data['block'];
   switch($block['block_type']){
    case 'hero':$this->render('hero',$data);break;
    case 'categories':$this->widget('CategoryTiles',array('rows'=>$data['categories']));break;
    case 'collection':$this->render('collection-block',$data);break;
    case 'latest_products':case 'popular_products':case 'category_products':$this->widget('ProductFeed',array('products'=>$data['products'],'block'=>$block,'title'=>$data['title'],'moreUrl'=>$data['moreUrl']));break;
    case 'blog':$this->render('blog-block',$data);break;
    case 'banner':echo '<section class="editorial-note"><span>BAZAARCHE / EVERYDAY OBJECTS</span><h2>'.CHtml::encode($block['title']?:'جزئیات کوچک، تجربه‌ای متفاوت').'</h2><p>'.CHtml::encode($data['description']).'</p></section>';break;
   }
  }
 }
}

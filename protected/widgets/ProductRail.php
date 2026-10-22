<?php
class ProductRail extends CWidget {
 public $products=array(),$presentation='grid',$style=null;
 public function run(){$carousel=$this->presentation==='carousel';$id=$this->getId();if(!$this->products){echo '<p class="muted">محصولی برای نمایش وجود ندارد.</p>';return;}if($carousel)echo '<div class="rail-controls"><button type="button" data-rail-dir="1" aria-controls="'.$id.'" aria-label="محصولات قبلی">→</button><button type="button" data-rail-dir="-1" aria-controls="'.$id.'" aria-label="محصولات بعدی">←</button></div>';echo '<div id="'.$id.'" class="'.($carousel?'product-rail':'product-grid').'"'.($carousel?' tabindex="0" role="region" aria-label="اسلایدر محصولات"':'').'>';foreach($this->products as $p)$this->widget('ProductCard',array('product'=>$p,'style'=>$this->style));echo '</div>';}
}

<?php
class CategoryTiles extends CWidget {
 public $parent=null,$rows=array();
 public function run(){$rows=$this->rows;if(!$rows)return;echo '<nav class="category-tiles" aria-label="دسته‌بندی محصولات">';foreach($rows as $row){$image=$row->image?MediaService::responsive($row->image,$row->name,array('loading'=>'lazy','sizes'=>'110px')):'<span class="category-symbol">✧</span>';echo CHtml::link($image.'<strong>'.CHtml::encode($row->name).'</strong>',array('shop/category','slug'=>$row->slug),array('data-pjax'=>'1'));}echo '</nav>';}
}

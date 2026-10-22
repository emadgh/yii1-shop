<?php
class NavigationWidget extends CWidget {
 public $location='header',$rows=array();
 private function items($rows,$parent=null,$depth=0){$items=array();if($depth>20)return $items;foreach($rows as $r)if((!$parent && !$r['parent_id']) || ($parent && (int)$r['parent_id']===(int)$parent)){$item=array('label'=>$r['label'],'url'=>$r['url'],'linkOptions'=>array('data-pjax'=>'1'));$children=$this->items($rows,$r['id'],$depth+1);if($children)$item['items']=$children;$items[]=$item;}return $items;}
 public function run(){$rows=$this->rows;$this->widget('zii.widgets.CMenu',array('items'=>$this->items($rows),'htmlOptions'=>array('class'=>'site-menu site-menu-'.$this->location),'submenuHtmlOptions'=>array('class'=>'site-submenu'),'activateParents'=>true));}
}

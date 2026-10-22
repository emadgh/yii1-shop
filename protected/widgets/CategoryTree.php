<?php
class CategoryTree extends CWidget {
 public $selected=0;
 public function run(){$rows=Hierarchy::rows('categories');$counts=Yii::app()->db->createCommand('SELECT category_id,COUNT(*) count FROM products GROUP BY category_id')->queryAll();$products=array();foreach($counts as $c)$products[$c['category_id']]=(int)$c['count'];$selected=null;foreach($rows as $row)if((int)$row['id']===(int)$this->selected)$selected=array('id'=>(int)$row['id'],'parent'=>(int)$row['parent_id'],'sort'=>(int)$row['sort_order']);$this->render('category-tree',array('rows'=>Hierarchy::flatten($rows),'revision'=>CategoryTreeService::revision($rows),'products'=>$products,'editorState'=>array('revision'=>CategoryTreeService::revision($rows),'options'=>Category::options($this->selected),'selected'=>$selected)));}
}

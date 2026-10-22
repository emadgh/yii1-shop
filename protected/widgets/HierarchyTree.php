<?php
/** Configurable Yii tree: rendering callbacks never supply SQL or JS. */
class HierarchyTree extends CWidget {
 public $type='categories',$location=null,$selected=0,$editorId='category-editor',$formId='category-form',$moveRoute='adminCategory/move',$editRoute='adminCategory/index',$title='درخت دسته‌بندی‌ها';
 public function run(){
  $base=Yii::app()->baseUrl.'/assets/';Yii::app()->clientScript->registerCssFile($base.'css/hierarchy-editor.css');Yii::app()->clientScript->registerScriptFile($base.'js/hierarchy-editor.js',CClientScript::POS_END);
  $service=new HierarchyTreeService($this->type,$this->location);$rows=$service->rows();$menu=$this->type==='menu_items';$counts=array();
  if(!$menu)foreach(Yii::app()->db->createCommand('SELECT category_id,COUNT(*) count FROM products GROUP BY category_id')->queryAll() as $row)$counts[$row['category_id']]=(int)$row['count'];
  $selected=null;foreach($rows as $row)if((int)$row['id']===(int)$this->selected)$selected=array('id'=>(int)$row['id'],'parent'=>(int)$row['parent_id'],'sort'=>(int)$row['sort_order']);
  $this->render('hierarchy-tree',array('rows'=>Hierarchy::flatten($rows),'revision'=>$service->revision($rows),'menu'=>$menu,'counts'=>$counts,'editorState'=>array('revision'=>$service->revision($rows),'options'=>$service->options($this->selected),'selected'=>$selected)));
 }
}

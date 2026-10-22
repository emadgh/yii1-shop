<?php
/** Configurable Yii tree: rendering callbacks never supply SQL or JS. */
class HierarchyTree extends CWidget {
 public $data=array(),$type='categories',$location=null,$selected=0,$editorId='category-editor',$formId='category-form',$moveRoute='adminCategory/move',$editRoute='adminCategory/index',$title='درخت دسته‌بندی‌ها';
 public function run(){
  $base=Yii::app()->baseUrl.'/assets/';Yii::app()->clientScript->registerCssFile($base.'css/hierarchy-editor.css');Yii::app()->clientScript->registerScriptFile($base.'js/hierarchy-editor.js',CClientScript::POS_END);
  $this->render('hierarchy-tree',$this->data);
 }
}

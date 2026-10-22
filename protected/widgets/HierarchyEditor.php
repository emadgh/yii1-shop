<?php
/** Shared tree / sticky editor workspace. IDs are configurable for independent instances. */
class HierarchyEditor extends CWidget {
 public $workspaceId='category-workspace',$treeId='category-tree',$editorId='category-editor',$formView,$formData=array(),$treeOptions=array();
 public function run(){
  $base=Yii::app()->baseUrl.'/assets/';Yii::app()->clientScript->registerCssFile($base.'css/hierarchy-editor.css');Yii::app()->clientScript->registerScriptFile($base.'js/hierarchy-editor.js',CClientScript::POS_END);
  echo CHtml::openTag('div',array('id'=>$this->workspaceId,'class'=>'category-workspace hierarchy-workspace','data-pjax-region'=>'1'));
  $this->widget('HierarchyTree',array_merge($this->treeOptions,array('id'=>$this->treeId,'editorId'=>$this->editorId)));
  echo CHtml::openTag('aside',array('id'=>$this->editorId,'class'=>'category-editor hierarchy-editor','data-pjax-region'=>'1','data-scroll-memory'=>''));$this->controller->renderPartial($this->formView,$this->formData);echo '</aside></div>';
 }
}

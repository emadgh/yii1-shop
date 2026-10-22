<?php
/** Flat, form-backed ordering with pointer handles and accessible move buttons. */
class SortableList extends CWidget {
 public $htmlOptions=array();
 public function init(){parent::init();Yii::app()->clientScript->registerCssFile(Yii::app()->baseUrl.'/assets/css/sortable-list.css');Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl.'/assets/js/sortable-list.js',CClientScript::POS_END);echo CHtml::openTag('div',array_merge(array('data-sortable-list'=>'1'),$this->htmlOptions));}
 public function run(){echo '</div>';}
}

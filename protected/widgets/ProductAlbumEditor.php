<?php
class ProductAlbumEditor extends CWidget {
 public $rows=array(),$revision='';
 public function run(){
  Yii::app()->clientScript->registerCssFile(Yii::app()->baseUrl.'/assets/css/albums.css');Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl.'/assets/js/album-editor.js',CClientScript::POS_END);
  $rows=array();foreach(is_array($this->rows)?$this->rows:array() as $row){if(!is_array($row))continue;$safe=array();foreach(array('id'=>0,'title'=>'','layout'=>'grid','media_ids'=>'') as $key=>$default)$safe[$key]=isset($row[$key])&&is_scalar($row[$key])?(string)$row[$key]:$default;if(!isset(ProductAlbum::layouts()[$safe['layout']]))$safe['layout']='grid';if(!preg_match('/^[0-9,]*$/D',$safe['media_ids']))$safe['media_ids']='';$rows[]=$safe;}
  $this->render('product-album-editor',array('rows'=>$rows));
 }
}

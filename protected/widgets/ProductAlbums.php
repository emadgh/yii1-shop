<?php
class ProductAlbums extends CWidget {
 public $product,$albums=array();
 public function run(){Yii::app()->clientScript->registerCssFile(Yii::app()->baseUrl.'/assets/css/albums.css');Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl.'/assets/js/albums.js',CClientScript::POS_END);if($this->albums)$this->render('product-albums',array('albums'=>$this->albums));}
}

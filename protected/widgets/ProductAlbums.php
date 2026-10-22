<?php
class ProductAlbums extends CWidget {
 public $product;
 public function run(){Yii::app()->clientScript->registerCssFile(Yii::app()->baseUrl.'/assets/css/albums.css');Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl.'/assets/js/albums.js',CClientScript::POS_END);$albums=ProductAlbum::model()->with('images')->findAllByAttributes(array('product_id'=>$this->product->id),array('order'=>'t.sort_order,t.id'));if($albums)$this->render('product-albums',array('albums'=>$albums));}
}

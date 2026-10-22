<?php
class ProductAlbum extends CActiveRecord {
 public static function model($className=__CLASS__){return parent::model($className);}
 public function tableName(){return 'product_albums';}
 public static function layouts(){return array('grid'=>'گرید · Grid','masonry'=>'ماسونری · Masonry','carousel'=>'کاروسل · Carousel','slideshow'=>'اسلایدشو · Slideshow');}
 public function rules(){return array(array('product_id,title,layout','required'),array('title','length','max'=>150),array('layout','in','range'=>array_keys(self::layouts())),array('product_id,sort_order','numerical','integerOnly'=>true,'min'=>0));}
 public function relations(){return array('product'=>array(self::BELONGS_TO,'Product','product_id'),'images'=>array(self::HAS_MANY,'ProductImage','album_id','order'=>'images.sort_order,images.id'));}
}

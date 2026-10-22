<?php
class ProductDetail extends CWidget {
 public $product,$albums=array(),$variants=array();
 public function run(){$layout=$this->product->display_layout;if(!$layout || $layout==='inherit')$layout=ThemeOptions::get()['product_layout'];if(!in_array($layout,array('split','gallery','editorial'),true))$layout='split';$this->render('product-detail',array('product'=>$this->product,'layout'=>$layout,'albums'=>$this->albums,'variants'=>$this->variants));}
}

<?php
/** Shared rendering for the navbar and the cart page, including AJAX responses. */
class CartContents extends CWidget {
 public $summary=null,$compact=false;
 public function run(){if($this->summary===null)$this->summary=Yii::app()->cart->summary();$this->render('cart-contents',array('summary'=>$this->summary,'compact'=>$this->compact));}
}

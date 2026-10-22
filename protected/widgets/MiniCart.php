<?php
class MiniCart extends CWidget {
 public function run(){
  $summary=Yii::app()->cart->summary();
  echo '<div class="navbar-cart"><button type="button" class="btn btn-outline" data-cart-toggle aria-expanded="false" aria-controls="navbar-cart-popup">سبد خرید <span class="cart-number">'.(int)$summary['count'].'</span></button><section id="navbar-cart-popup" class="navbar-cart-popup" aria-label="سبد خرید" hidden><div class="mini-cart-heading"><strong>سبد خرید شما</strong><button type="button" data-cart-close aria-label="بستن سبد خرید">×</button></div><div data-mini-cart-content>';
  $this->widget('CartContents',array('summary'=>$summary,'compact'=>true));
  echo '</div></section></div>';
 }
}

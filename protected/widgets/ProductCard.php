<?php
class ProductCard extends CWidget {
 public $product,$style=null;
 public function run(){$p=$this->product;$style=$p->card_layout && $p->card_layout!=='inherit'?$p->card_layout:($this->style?:ThemeOptions::get()['card_style']);if(!in_array($style,array('standard','compact','editorial'),true))$style='standard';$this->render('product-card',array('product'=>$p,'style'=>$style));}
}

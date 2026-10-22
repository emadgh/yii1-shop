<?php
class CheckoutLines extends CWidget {
 public $summary;
 public function run(){foreach($this->summary['items'] as $item)echo '<div class="summary-row"><span>'.CHtml::encode($item['product']->title).' × '.(int)$item['quantity'].'</span><strong>'.Money::format($item['lineTotal']).'</strong></div>';}
}

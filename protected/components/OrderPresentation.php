<?php
class OrderPresentation {
 public static function badge($status){$labels=Order::model()->statuses();$key=isset($labels[$status])?$status:'unknown';return '<span class="status status-'.$key.'">'.CHtml::encode($labels[$status]??'نامشخص').'</span>';}
 public static function item($item){$p=$item->product;$image=$p&&$p->images?MediaService::responsive($p->images[0]->filename,$item->product_title,array('class'=>'order-item-thumb','loading'=>'lazy','sizes'=>'56px')):'<span class="order-item-thumb order-item-placeholder" aria-hidden="true">▧</span>';$content=$image.'<span>'.CHtml::encode($item->product_title).(!$p?'<small>محصول حذف شده است</small>':'').'</span>';return $p?CHtml::link($content,$p->status==='published'?$p->link():Yii::app()->createUrl('adminProduct/update',array('id'=>$p->id)),array('class'=>'order-item-product','data-pjax'=>'1')):'<div class="order-item-product">'.$content.'</div>';}
}

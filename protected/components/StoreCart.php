<?php
class StoreCart extends CApplicationComponent {
 public function items(){$items=Yii::app()->session->itemAt('cart');return is_array($items)?$items:array();}
 public function count(){return $this->summary()['count'];}
 private function integer($value,$minimum=0){if(!is_scalar($value)||!preg_match('/^[0-9]{1,9}$/D',(string)$value)||(int)$value<$minimum)throw new CHttpException(422,'تعداد یا شناسه محصول معتبر نیست.');return (int)$value;}
 public function setQuantity($id,$quantity){
  $id=$this->integer($id,1);$quantity=$this->integer($quantity);$items=$this->items();
  if(!$quantity)unset($items[$id]);else{$product=Product::model()->findByPk($id);if(!$product||$product->status!=='published')throw new CHttpException(404,'محصول پیدا نشد.');if(!$product->parent_product_id&&$product->variants())throw new CHttpException(422,'ابتدا رنگ یا سایز محصول را انتخاب کنید.');if($product->stock<=0)throw new CHttpException(422,'موجودی محصول تمام شده است.');$items[$id]=min(99,(int)$product->stock,$quantity);}
  Yii::app()->session->add('cart',$items);
 }
 public function add($id,$quantity){$id=$this->integer($id,1);$quantity=$this->integer($quantity,1);$this->summary();$items=$this->items();$this->setQuantity($id,($items[$id]??0)+min(99,$quantity));}
 public function clear(){Yii::app()->session->remove('cart');}
 public function summary(){
  $stored=$this->items();$result=array();$normalized=array();$total=0;$count=0;
  $products=$stored?Product::model()->with('images')->findAllByPk(array_keys($stored),array('condition'=>"t.status='published'")):array();$byId=array();foreach($products as $p)$byId[$p->id]=$p;
  foreach($stored as $id=>$qty){if(!isset($byId[$id])||!is_scalar($qty))continue;$p=$byId[$id];$qty=min(99,(int)$qty,(int)$p->stock);if($qty<=0)continue;$normalized[$id]=$qty;$line=$p->salePrice()*$qty;$total+=$line;$count+=$qty;$result[]=array('product'=>$p,'quantity'=>$qty,'lineTotal'=>$line);}
  if($normalized!==$stored)Yii::app()->session->add('cart',$normalized);
  return array('items'=>$result,'total'=>$total,'count'=>$count);
 }
}

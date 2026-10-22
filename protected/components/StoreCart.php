<?php
class StoreCart extends CApplicationComponent
{
    public function items() {
      $items=Yii::app()->session->itemAt('cart');
      return is_array($items) ? $items : array();
    }
    public function count() { return array_sum($this->items()); }
    public function setQuantity($id,$quantity) {
      $id=(int)$id; $quantity=(int)$quantity;
      $items=$this->items();
      if($quantity<=0) unset($items[$id]);
      else {
        $product=Product::model()->findByPk($id);
        if(!$product || $product->status!=='published' || $id<=0) throw new CHttpException(404,'محصول پیدا نشد.');
        $items[$id]=min(99,(int)$product->stock,$quantity);
      }
      Yii::app()->session->add('cart',$items);
    }
    public function add($id,$quantity) {
      $items=$this->items();
      $this->setQuantity($id,(isset($items[(int)$id]) ? $items[(int)$id] : 0)+max(1,min(99,(int)$quantity)));
    }
    public function clear() { Yii::app()->session->remove('cart'); }
    public function summary() {
      $result=array(); $total=0;
      foreach($this->items() as $id=>$qty) {
        $p=Product::model()->findByPk((int)$id);
        if(!$p || $p->status!=='published') continue;
        $qty=min((int)$qty,(int)$p->stock);
        if($qty<=0) continue;
        $line=$p->salePrice()*$qty;
        $result[]=array('product'=>$p,'quantity'=>$qty,'lineTotal'=>$line);
        $total+=$line;
      }
      return array('items'=>$result,'total'=>$total);
    }
}

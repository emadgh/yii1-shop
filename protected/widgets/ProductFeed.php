<?php
class ProductFeed extends CWidget
{
    public $products=array(),$block=array(),$title='',$moreUrl=null;
    public function run()
    {
        $this->render('product-feed',array('products'=>$this->products,'block'=>$this->block,'title'=>$this->title,'moreUrl'=>$this->moreUrl));
    }
}

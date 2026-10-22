<?php
class Product extends BaseRecord
{
    public static function model($className=__CLASS__) { return parent::model($className); }
    public function tableName() { return 'products'; }
    public function rules() { return array(
      array('title, sku, price, stock, status','required'),array('parent_product_id','numerical','integerOnly'=>true,'min'=>1,'allowEmpty'=>true),array('parent_product_id','validateParentProduct'),array('variant_label','length','max'=>190),
      array('title, seo_title','length','max'=>255),array('sku','length','max'=>120),
      array('slug','length','max'=>190),array('slug, sku','unique'),
      array('slug','match','pattern'=>'/^[a-z0-9]+(?:-[a-z0-9]+)*$/','message'=>'Slug باید انگلیسی و شامل حروف کوچک، ارقام یا خط تیره باشد.'),
      array('price, compare_price','numerical','integerOnly'=>true,'min'=>0,'max'=>1000000000000),
      array('stock','numerical','integerOnly'=>true,'min'=>0,'max'=>1000000),
      array('category_id','numerical','integerOnly'=>true,'min'=>1,'allowEmpty'=>true),
      array('status','in','range'=>array('draft','published')),
      array('featured','boolean'),
      array('discount_type','in','range'=>array('none','percent','fixed')),
      array('discount_value','numerical','integerOnly'=>true,'min'=>0,'max'=>1000000000000),
      array('discount_value','validateDiscount'),
      array('summary, description, seo_description','safe'),
      array('display_layout','in','range'=>array('inherit','split','gallery','editorial')),array('card_layout','in','range'=>array('inherit','standard','compact','editorial')),
    ); }
    public function relations() { return array(
      'category'=>array(self::BELONGS_TO,'Category','category_id'),
      'albums'=>array(self::HAS_MANY,'ProductAlbum','product_id','order'=>'albums.sort_order ASC, albums.id ASC'),
      'images'=>array(self::HAS_MANY,'ProductImage','product_id','order'=>'images.sort_order ASC, images.id ASC'),
      'specifications'=>array(self::HAS_MANY,'ProductAttribute','product_id','order'=>'specifications.sort_order ASC'),
      'metadata'=>array(self::HAS_MANY,'ProductMeta','product_id','order'=>'metadata.meta_key ASC'),
      'tags'=>array(self::MANY_MANY,'Tag','product_tag(product_id,tag_id)'),
    ); }
    protected function beforeValidate() {
       if(!$this->slug) $this->slug=Slug::unique('Product',$this->title,$this->isNewRecord ? null : $this->id);
       return parent::beforeValidate();
    }
    public function validateParentProduct(){if(!$this->parent_product_id){$this->parent_product_id=null;return;}$parent=self::model()->findByPk($this->parent_product_id);if(!$parent||$parent->parent_product_id||$parent->id==$this->id||(!$this->isNewRecord&&self::model()->countByAttributes(array('parent_product_id'=>$this->id))))$this->addError('parent_product_id','محصول والد معتبر نیست؛ تنوع نمی‌تواند والد تنوع دیگری باشد.');}
    public function variants(){return self::model()->findAllByAttributes(array('parent_product_id'=>$this->parent_product_id?:$this->id,'status'=>'published'),array('order'=>'id'));}
    public function cover() { return $this->images ? UploadService::url($this->images[0]->filename) : ''; }
    public function link() { return Yii::app()->createUrl('shop/view',array('slug'=>$this->slug)); }
    public function tagsText() { return implode(', ',array_map(function($tag){return $tag->name;},$this->tags)); }
    public function validateDiscount($attribute,$params) { if(($this->discount_type==='percent' && $this->discount_value>100) || ($this->discount_type==='fixed' && $this->discount_value>$this->price)) $this->addError($attribute,'تخفیف از قیمت یا ۱۰۰ درصد بیشتر است.'); }
    public static function effectivePrice($row) {
      $price=(int)$row['price']; $value=(int)$row['discount_value'];
      return max(0,$price-($row['discount_type']==='percent'?intdiv($price*$value,100):($row['discount_type']==='fixed'?$value:0)));
    }
    public function salePrice() { return self::effectivePrice($this->attributes); }
    public function discountPercent() { return $this->price>0?(int)round(100*(1-$this->salePrice()/$this->price)):0; }
}

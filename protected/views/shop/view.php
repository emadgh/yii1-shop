<?php if(!empty($isPreview)): ?><div class="container"><div class="flash" role="status">پیش‌نمایش اطلاعات ذخیره‌شدهٔ محصول؛ این صفحه فقط برای مدیر محصول قابل مشاهده است. <?= CHtml::link('بازگشت به ویرایش',array('adminProduct/update','id'=>$product->id)) ?></div></div><?php endif; ?>
<?php $this->widget("ProductDetail",array("product"=>$product,"albums"=>$albums,"variants"=>$variants)); ?>

<div class="admin-page-head"><div><h1>دسته‌بندی‌های محصولات</h1><p>ساختار درختی را مرتب کنید و جزئیات هر دسته را کنار آن ویرایش کنید.</p></div><?= CHtml::link('+ دسته‌بندی جدید',array('adminCategory/index'),array('class'=>'btn btn-primary','data-pjax'=>'1','data-pjax-target'=>'#category-editor','data-pjax-reset-scroll'=>'1')) ?></div>
<div id="category-workspace" class="category-workspace" data-pjax-region="1">
<?php $this->widget('CategoryTree',array('selected'=>(int)$model->id)); ?>
<aside id="category-editor" class="category-editor" data-pjax-region="1" data-scroll-memory><?php $this->renderPartial('//admin/categories/form',array('model'=>$model)); ?></aside>
</div>

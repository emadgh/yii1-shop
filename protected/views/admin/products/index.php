<div class="admin-page-head"><div><span class="eyebrow">مدیریت کاتالوگ</span><h1>محصولات</h1><p>ثبت و ویرایش محصولات، موجودی، تصاویر و SEO</p></div><?= CHtml::link('+ محصول جدید',array('adminProduct/create'),array('class'=>'btn btn-primary','data-pjax'=>'1')) ?></div>
<section class="panel"><div class="panel-heading"><h2>فهرست محصولات</h2>
<?= CHtml::beginForm(array('adminProduct/index'),'get',array('class'=>'inline-search','data-pjax-form'=>'1')) ?><?= CHtml::textField('q',$q,array('placeholder'=>'نام، مشخصات، متادیتا و تگ…')) ?><?= CHtml::submitButton('جستجو',array('class'=>'btn btn-outline btn-sm')) ?><?= CHtml::endForm() ?></div>
<div class="table-wrap"><?php $this->widget('zii.widgets.grid.CGridView',array(
 'id'=>'products-grid','ajaxUpdate'=>false,'dataProvider'=>$provider,'enableSorting'=>false,'summaryText'=>'نمایش {start} تا {end} از {count}',
 'itemsCssClass'=>'data-table','columns'=>array(
  array('name'=>'id','header'=>'شناسه'),
  array('name'=>'title','header'=>'نام محصول'),
  array('name'=>'sku','header'=>'SKU'),
  array('name'=>'price','header'=>'قیمت فروش','value'=>'Money::format($data->salePrice())'),
  array('name'=>'stock','header'=>'موجودی'),
  array('name'=>'status','header'=>'وضعیت','value'=>'$data->status==="published"?"منتشرشده":"پیش‌نویس"'),
  array('header'=>'عملیات','type'=>'raw','value'=>'CHtml::link("ویرایش ←",array("adminProduct/update","id"=>$data->id),array("class"=>"table-action","data-pjax"=>"1"))'),
 ),'pager'=>array('header'=>'','htmlOptions'=>array('class'=>'pagination')))); ?></div></section>

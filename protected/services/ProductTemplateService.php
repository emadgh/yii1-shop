<?php
class ProductTemplateService
{
    public static function prefill($product,ProductEditForm $input,$id)
    {
        if(!$product->isNewRecord)throw new CHttpException(422,'پیش‌پرکردن فقط برای محصول جدید مجاز است.');
        $template=ProductTemplateQuery::data($id);$data=$template['data'];
        $product->attributes=$data['fields'];$input->tagsText=$data['tags'];$input->specs=$data['specs'];$input->meta=$data['meta'];
        return $template['name'];
    }
    public static function capture($product,$name)
    {
        if(!is_string($name)||!trim($name)||mb_strlen(trim($name))>150)throw new CHttpException(422,'نام قالب را با حداکثر ۱۵۰ کاراکتر وارد کنید.');
        $template=new ProductTemplate;$template->name=trim($name);$template->created_at=date('Y-m-d H:i:s');
        $template->payload=CJSON::encode(ProductTemplateQuery::snapshot($product));
        if(!$template->save())throw new CHttpException(422,implode(' ',array_map(function($errors){return implode(' ',$errors);},$template->errors)));
        return $template;
    }
    public static function delete($id){$template=ProductTemplateQuery::find($id);if(!$template->delete())throw new CHttpException(422,'حذف قالب انجام نشد.');}
}

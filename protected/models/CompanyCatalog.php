<?php
class CompanyCatalog extends BaseRecord
{
    public static function model($className=__CLASS__) { return parent::model($className); }
    public function tableName() { return 'company_catalogs'; }
    public function rules()
    {
        return array(array('title,pdf_id','required'), array('title','length','max'=>180),
            array('description','length','max'=>100000), array('is_active','boolean'),
            array('sort_order','numerical','integerOnly'=>true,'min'=>0,'max'=>99999));
    }
    public function attributeLabels()
    {
        return array('title'=>'عنوان','description'=>'توضیحات','pdf_id'=>'فایل PDF','is_active'=>'قابل نمایش','sort_order'=>'ترتیب');
    }
}

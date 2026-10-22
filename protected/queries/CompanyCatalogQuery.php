<?php
class CompanyCatalogQuery
{
    public static function published()
    {
        return SiteCache::read('company-catalogs', array('company_catalogs','media'), function () {
            return Yii::app()->db->createCommand('SELECT c.*,i.filename image_filename,p.filename pdf_filename,p.size pdf_size FROM company_catalogs c LEFT JOIN media i ON i.id=c.image_id JOIN media p ON p.id=c.pdf_id WHERE c.is_active=1 AND p.mime=\'application/pdf\' ORDER BY c.sort_order,c.id DESC')->queryAll();
        });
    }

    public static function download($id)
    {
        // Publication is checked fresh even when a user follows an old cached link.
        return Yii::app()->db->createCommand('SELECT c.title,p.filename FROM company_catalogs c JOIN media p ON p.id=c.pdf_id WHERE c.id=:id AND c.is_active=1 AND p.mime=\'application/pdf\'')->queryRow(true,array(':id'=>$id));
    }
}

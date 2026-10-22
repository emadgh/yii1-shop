<?php
class UploadService
{
    public static function store($upload,$subdir)
    {
        if(!in_array($subdir,array('products','blog'),true)) throw new CException('Invalid upload directory');
        if(!$upload || $upload->getHasError() || $upload->getSize()>5*1024*1024 || $upload->getSize()<=0)
            throw new CException('فایل معتبر نیست یا بیشتر از ۵ مگابایت است.');
        $mime=function_exists('mime_content_type') ? mime_content_type($upload->getTempName()) : '';
        $types=array('image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp');
        if(!isset($types[$mime]) || @getimagesize($upload->getTempName())===false)
            throw new CException('فقط تصاویر واقعی JPG، PNG و WebP مجازند.');
        $filename=bin2hex(random_bytes(16)).'.'.$types[$mime];
        $relative=$subdir.'/'.$filename;
        $dest=Yii::app()->params['uploadsDir'].'/'.$relative;
        if(!is_dir(dirname($dest)) && !mkdir(dirname($dest),0755,true)) throw new CException('ساخت پوشه ممکن نیست.');
        if(!$upload->saveAs($dest)) throw new CException('ذخیره تصویر ناموفق بود.');
        chmod($dest,0644);
        return $relative;
    }
    public static function remove($relative)
    {
        if(!preg_match('~^(products|blog)/[a-f0-9]{32}\.(jpg|png|webp)$~D',(string)$relative)) return;
        $path=Yii::app()->params['uploadsDir'].'/'.$relative;
        if(is_file($path)) @unlink($path);
    }
    public static function url($relative) {
       if(!$relative || !preg_match('~^(?:(?:products|blog)/[a-f0-9]{32}\.(?:jpg|png|webp)|media/[a-f0-9]{32}(?:-(?:thumb|medium|large))?\.(?:webp|pdf|txt))$~D',$relative)) return '';
       return Yii::app()->baseUrl.'/uploads/'.$relative;
    }
}

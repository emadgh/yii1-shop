<?php
class Slug
{
    public static function make($text,$fallback='item')
    {
        $text=mb_strtolower(trim((string)$text),'UTF-8');
        $text=preg_replace('/[^\p{L}\p{N}]+/u','-',$text);
        $text=trim($text,'-');
        // URL rules allow ASCII slugs; Persian titles receive a stable ASCII fallback.
        if(!preg_match('/^[a-z0-9-]+$/',$text)) return $fallback.'-'.bin2hex(random_bytes(4));
        return $text ?: $fallback.'-'.bin2hex(random_bytes(4));
    }
    public static function unique($class,$title,$ignoreId=null)
    {
       $base=self::make($title); $slug=$base; $i=2;
       while(true) {
         $found=call_user_func(array($class,'model'))->findByAttributes(array('slug'=>$slug));
         if(!$found || ($ignoreId!==null && (int)$found->id===(int)$ignoreId)) return $slug;
         $slug=$base.'-'.$i++;
       }
    }
}

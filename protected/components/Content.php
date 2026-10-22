<?php
class Content {
 public static function markdown($text) {
   $parser=new CMarkdownParser;
   $purifier=new CHtmlPurifier;
   $purifier->options=array('HTML.Allowed'=>'p,br,strong,em,h1,h2,h3,h4,ul,ol,li,blockquote,pre,code,a[href|title],img[src|alt|title],hr,table,thead,tbody,tr,th,td','URI.AllowedSchemes'=>array('http'=>true,'https'=>true),'Attr.EnableID'=>false);
   return $purifier->purify($parser->transform((string)$text));
 }
 public static function plain($text) { return strip_tags(self::markdown($text)); }
}

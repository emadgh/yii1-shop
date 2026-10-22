<?php
class SeoController extends AppController {
 public function actionSitemap(){
  $base=rtrim(ShopSettings::get('public_url',''),'/');if(!filter_var($base,FILTER_VALIDATE_URL))throw new CHttpException(503,'آدرس عمومی سایت در تنظیمات وارد نشده است.');header('Content-Type: application/xml; charset=utf-8');echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';$paths=array('/','/shop','/blog');foreach(Product::model()->findAllByAttributes(array('status'=>'published')) as $p)$paths[]='/product/'.$p->slug;foreach(Post::model()->findAllByAttributes(array('status'=>'published')) as $p)$paths[]='/blog/'.$p->slug;foreach(Category::model()->findAll() as $c)$paths[]='/category/'.$c->slug;foreach($paths as $path)echo '<url><loc>'.htmlspecialchars($base.$path,ENT_XML1|ENT_QUOTES,'UTF-8').'</loc></url>';echo '</urlset>';Yii::app()->end();
 }
 public function actionRobots(){header('Content-Type: text/plain; charset=utf-8');echo "User-agent: *\nDisallow: /admin\nDisallow: /cart\nDisallow: /checkout\nDisallow: /payment/\nDisallow: /tracking/\nDisallow: /login\n";$base=rtrim(ShopSettings::get('public_url',''),'/');if(filter_var($base,FILTER_VALIDATE_URL))echo 'Sitemap: '.$base.'/sitemap.xml';Yii::app()->end();}
}

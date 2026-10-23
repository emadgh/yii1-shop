<?php
/** Admin navigation grouped by task; empty groups disappear for restricted roles. */
class AdminNavigation
{
    public static function groups()
    {
        $groups=array(
            'داشبورد و آمار'=>array(
                'adminDashboard/index'=>'◈ نمای کلی',
                'adminAnalytics/index'=>'▥ آمار بازدید',
            ),
            'محصولات'=>array(
                'adminProduct/index'=>'▣ محصولات',
                'adminCategory/index'=>'◫ دسته‌بندی‌ها',
                'adminCollection/index'=>'◧ کالکشن‌ها',
                'adminTag/index'=>'⌗ تگ‌ها',
            ),
            'محتوا و رسانه'=>array(
                'adminPost/index'=>'▤ بلاگ',
                'adminCatalog/index'=>'▥ کاتالوگ‌های شرکت',
                'adminMedia/index'=>'▧ کتابخانه رسانه',
            ),
            'فروش و حسابداری'=>array(
                'adminOrder/index'=>'◉ سفارش‌ها',
                'adminCoupon/index'=>'◇ کدهای تخفیف',
                'adminReport/index'=>'↗ مالی و انبار',
            ),
            'ظاهر و ناوبری'=>array(
                'adminTheme/index'=>'◐ مدیریت قالب‌ها',
                'adminMenu/index'=>'☷ مدیریت منو',
                'adminSeo/index'=>'↪ تغییر آدرس صفحات',
            ),
            'سیستم و امنیت'=>array(
                'adminSettings/index'=>'⚙ تنظیمات',
                'adminUser/index'=>'♙ کاربران مدیریت',
                'adminApi/index'=>'⌘ دسترسی AI و API',
                'adminOperations/index'=>'≡ عملیات و تاریخچه',
                'adminUpgrade/index'=>'↑ ارتقای دیتابیس',
                'adminSecurity/index'=>'⌑ امنیت حساب',
            ),
            'میانبرها'=>array('site/index'=>'↗ مشاهده فروشگاه'),
        );
        $superOnly=array('adminTheme/index','adminSeo/index','adminUser/index','adminApi/index','adminOperations/index','adminUpgrade/index');
        $result=array();$super=AccessService::isSuperAdmin();
        foreach($groups as $title=>$items){
            $visible=array();
            foreach($items as $route=>$label){
                if(!AccessService::canRoute($route)||(!$super&&in_array($route,$superOnly,true)))continue;
                $visible[]=array('label'=>$label,'route'=>array($route),'options'=>in_array($route,array('adminApi/index','site/index'),true)?array('data-no-pjax'=>'1'):array('data-pjax'=>'1'));
            }
            if($visible)$result[]=array('title'=>$title,'items'=>$visible);
        }
        return $result;
    }
}

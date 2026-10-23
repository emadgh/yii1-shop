<?php
/** Runs the same trusted migrations as the CLI, inside an authenticated web request. */
class DatabaseUpgradeService
{
    public static function run()
    {
        if(!AccessService::isSuperAdmin())throw new CHttpException(403);
        if(!Yii::app()->request->isPostRequest)throw new CHttpException(405);
        $lock=@fopen(Yii::app()->runtimePath.'/database-upgrade.lock','c');
        if(!$lock)throw new RuntimeException('فایل قفل ارتقا ساخته نشد؛ دسترسی نوشتن پوشه protected/runtime را بررسی کنید.');
        if(!flock($lock,LOCK_EX|LOCK_NB)){fclose($lock);throw new RuntimeException('یک ارتقای دیگر در حال اجراست؛ پس از پایان آن دوباره بررسی کنید.');}
        $level=ob_get_level();ob_start();@set_time_limit(180);
        try{
            if(!defined('BAZAARCHE_WEB_UPGRADE'))define('BAZAARCHE_WEB_UPGRADE',true);
            self::migrate();
            Yii::app()->db->schema->refresh();ShopSettings::reset();Yii::app()->siteCache->invalidateAll();
            ShopSettings::set('database_upgraded_at',date('Y-m-d H:i:s'));
            Audit::record('database','schema','upgrade');
            return trim(ob_get_contents());
        }catch(Throwable $exception){
            error_log('Web database upgrade failed ('.get_class($exception).', code '.(int)$exception->getCode().').');
            throw new RuntimeException('ارتقا کامل نشد. دسترسی‌های دیتابیس، کامل‌بودن فایل‌های آپلودشده و محدودیت زمان اجرای PHP را بررسی کنید. پس از رفع مشکل، ارتقا را دوباره اجرا کنید.');
        }finally{while(ob_get_level()>$level)ob_end_clean();flock($lock,LOCK_UN);fclose($lock);}
    }
    private static function migrate(){require dirname(__DIR__,2).'/tools/upgrade.php';}
}

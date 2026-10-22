<?php
class Commerce {
 public static function enabled(){return ShopSettings::get('commerce_enabled','1')==='1';}
 public static function showPrices(){return self::enabled() || ShopSettings::get('catalog_show_prices','1')==='1';}
 public static function requireEnabled(){if(!self::enabled())throw new CHttpException(403,'فروشگاه در حالت کاتالوگ است؛ ثبت سفارش و سبد خرید غیرفعال هستند.');}
}

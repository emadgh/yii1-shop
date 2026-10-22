<?php
class ApiTokenService
{
    public static $actor;
    public static function scopes()
    {
        return array('content:read'=>'خواندن محتوا','content:write'=>'ویرایش محتوا','products:write'=>'ویرایش محصولات، قیمت و موجودی','coupons:write'=>'مدیریت کدهای تخفیف','content:publish'=>'انتشار محتوا',
            'content:delete'=>'حذف محتوا','media:write'=>'آپلود رسانه','orders:read'=>'خواندن سفارش و اطلاعات مشتری',
            'orders:write'=>'تغییر وضعیت سفارش','reports:read'=>'آمار و گزارش‌ها','settings:write'=>'تنظیمات عمومی و قالب');
    }
    public static function allowedScopes($role)
    {
        if($role==='superadmin')return array_keys(self::scopes());
        if($role==='admin')return array_values(array_diff(array_keys(self::scopes()),array('settings:write')));
        return array('content'=>array('content:read','content:write','content:publish','content:delete','media:write'),
            'sales'=>array('orders:read','orders:write'),'warehouse'=>array('reports:read'))[$role]??array();
    }
    public static function issue($userId,$name,$scopes,$days=90)
    {
        if(!is_array($scopes)||count($scopes)>count(self::scopes()))throw new CHttpException(422,'مجوزها معتبر نیستند.');
        foreach($scopes as $scope)if(!is_string($scope))throw new CHttpException(422,'مجوزها معتبر نیستند.');
        $user=User::model()->findByPk($userId);
        if(!$user||!$user->is_active||!is_string($name)||!trim($name)||mb_strlen($name)>100||!is_array($scopes)||!$scopes||array_diff($scopes,self::allowedScopes($user->role))||$days<1||$days>365)throw new CHttpException(422,'اطلاعات یا سطح دسترسی توکن معتبر نیست.');
        $secret='bz_'.bin2hex(random_bytes(32));
        Yii::app()->db->createCommand()->insert('api_tokens',array('user_id'=>$userId,'name'=>trim($name),'token_hash'=>hash('sha256',$secret),
            'scopes'=>CJSON::encode(array_values(array_unique($scopes))),'expires_at'=>date('Y-m-d H:i:s',time()+86400*$days),'created_at'=>date('Y-m-d H:i:s')));
        return array('id'=>(int)Yii::app()->db->lastInsertID,'token'=>$secret);
    }
    public static function authenticate($authorization)
    {
        if(ShopSettings::get('ai_api_enabled','0')!=='1')throw new CHttpException(503,'AI API غیرفعال است.');
        if(!is_string($authorization)||!preg_match('/^Bearer (bz_[a-f0-9]{64})$/D',$authorization,$match))throw new CHttpException(401,'توکن Bearer معتبر لازم است.');
        $row=Yii::app()->db->createCommand('SELECT t.*,u.role,u.is_active FROM api_tokens t JOIN users u ON u.id=t.user_id WHERE t.token_hash=:hash')->queryRow(true,array(':hash'=>hash('sha256',$match[1])));
        if(!$row||!$row['is_active']||$row['revoked_at']||$row['expires_at']<=date('Y-m-d H:i:s'))throw new CHttpException(401,'توکن منقضی یا غیرفعال است.');
        $row['permissions']=array_values(array_intersect(json_decode($row['scopes'],true)?:array(),self::allowedScopes($row['role'])));
        // Atomic per-token window; authenticated requests are limited to 120/minute.
        $db=Yii::app()->db;$minute=date('Y-m-d H:i');$key='rate-'.$minute;
        $tx=$db->beginTransaction();
        try{
            $db->createCommand('UPDATE api_tokens SET last_used_at=:now WHERE id=:id')->execute(array(':now'=>date('Y-m-d H:i:s'),':id'=>$row['id']));
            $count=(int)$db->createCommand('SELECT COUNT(*) FROM api_requests WHERE token_id=:id AND created_at>=:since AND request_key LIKE :pattern')->queryScalar(array(':id'=>$row['id'],':since'=>$minute.':00',':pattern'=>'rate-%'));
            if($count>=120)throw new CHttpException(429,'تعداد درخواست بیش از حد مجاز است.');
            $db->createCommand()->insert('api_requests',array('token_id'=>$row['id'],'request_key'=>$key.'-'.bin2hex(random_bytes(6)),'request_hash'=>str_repeat('0',64),'response_json'=>'{}','created_at'=>date('Y-m-d H:i:s')));
            $db->createCommand()->delete('api_requests','token_id=:id AND created_at<:cutoff AND request_key LIKE :pattern',array(':id'=>$row['id'],':cutoff'=>date('Y-m-d H:i:s',time()-3600),':pattern'=>'rate-%'));
            $tx->commit();
        }catch(Exception $e){if($tx->active)$tx->rollback();throw $e;}
        self::$actor=$row;return $row;
    }
    public static function requireScope($scope)
    {
        if(!self::$actor||!in_array($scope,self::$actor['permissions'],true))throw new CHttpException(403,'مجوز لازم وجود ندارد: '.$scope);
    }
}

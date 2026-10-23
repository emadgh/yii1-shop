<?php
/** Standalone bootstrap for a new deployment. Never overwrites an existing site. */
class InstallerService
{
    private $root;
    private $phase='requirements';
    public function __construct($root){$this->root=rtrim($root,'/\\');}
    public function installed()
    {
        return is_file($this->root.'/protected/config/local.php')||is_file($this->root.'/protected/runtime/install.lock')
            ||getenv('SHOP_DSN')||is_file($this->root.'/protected/runtime/store.sqlite');
    }
    public function requirements()
    {
        $checks=array(array('label'=>'PHP 7.4 یا جدیدتر','ok'=>version_compare(PHP_VERSION,'7.4','>='),'detail'=>'نسخه فعلی: '.PHP_VERSION));
        foreach(array('pdo_mysql'=>'اتصال MySQL / MariaDB','mbstring'=>'متن فارسی','fileinfo'=>'تشخیص نوع فایل','openssl'=>'رمزنگاری','gd'=>'پردازش تصاویر') as $extension=>$label)
            $checks[]=array('label'=>$label,'ok'=>extension_loaded($extension),'detail'=>'افزونه '.$extension);
        $checks[]=array('label'=>'پشتیبانی WebP','ok'=>function_exists('imagewebp'),'detail'=>'برای تصاویر چنداندازه');
        $checks[]=array('label'=>'کتابخانه‌های PHP','ok'=>is_file($this->root.'/vendor/autoload.php')&&is_file($this->root.'/vendor/yiisoft/yii/framework/yii.php'),'detail'=>'پوشه vendor باید همراه پروژه آپلود شود.');
        foreach(array('protected/config','protected/runtime','public/uploads','public/assets') as $path){
            $checks[]=array('label'=>'دسترسی نوشتن '.$path,'ok'=>is_dir($this->root.'/'.$path)&&is_writable($this->root.'/'.$path),'detail'=>'پوشه باید برای PHP قابل نوشتن باشد.');
        }
        return $checks;
    }
    public function databaseValues($input)
    {
        $out=array();foreach(array('db_host','db_port','db_name','db_user','db_password') as $key){if(!isset($input[$key])||!is_string($input[$key]))throw new InvalidArgumentException('اطلاعات دیتابیس کامل نیست.');$out[$key]=$key==='db_password'?$input[$key]:trim($input[$key]);}
        if(!preg_match('/^[a-zA-Z0-9._:\[\]-]{1,253}$/D',$out['db_host'])||!ctype_digit($out['db_port'])||(int)$out['db_port']<1||(int)$out['db_port']>65535)throw new InvalidArgumentException('آدرس یا پورت دیتابیس معتبر نیست.');
        if(!preg_match('/^[a-zA-Z0-9_$-]{1,64}$/D',$out['db_name'])||!$out['db_user']||strlen($out['db_user'])>100||strpos($out['db_user'],"\0")!==false)throw new InvalidArgumentException('نام دیتابیس یا کاربر آن معتبر نیست.');
        return $out;
    }
    public function validate($input)
    {
        $out=$this->databaseValues($input);
        foreach(array('site_name'=>100,'site_description'=>500,'site_url'=>500,'admin_name'=>100,'admin_email'=>190) as $key=>$limit){
            if(!isset($input[$key])||!is_string($input[$key]))throw new InvalidArgumentException('اطلاعات سایت و مدیر کامل نیست.');
            $out[$key]=trim($input[$key]);if(mb_strlen($out[$key])>$limit)throw new InvalidArgumentException('یکی از متن‌ها بیش از حد بلند است.');
        }
        if(!$out['site_name']||!$out['admin_name']||!filter_var($out['admin_email'],FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('نام سایت، نام مدیر و ایمیل معتبر را وارد کنید.');
        $out['admin_email']=strtolower($out['admin_email']);$out['site_url']=rtrim($out['site_url'],'/');$url=parse_url($out['site_url']);
        if(!filter_var($out['site_url'],FILTER_VALIDATE_URL)||!in_array($url['scheme']??'',array('http','https'),true)||isset($url['user'])||isset($url['pass'])||isset($url['query'])||isset($url['fragment']))throw new InvalidArgumentException('آدرس سایت باید یک آدرس کامل HTTP یا HTTPS و بدون پارامتر باشد.');
        if(!isset($input['admin_password'],$input['admin_password_confirm'])||!is_string($input['admin_password'])||!is_string($input['admin_password_confirm'])||strlen($input['admin_password'])<12||strlen($input['admin_password'])>72)throw new InvalidArgumentException('رمز مدیر باید بین ۱۲ تا ۷۲ بایت باشد.');
        if(!hash_equals($input['admin_password'],$input['admin_password_confirm']))throw new InvalidArgumentException('رمز عبور و تکرار آن یکسان نیستند.');
        if(!isset($input['sample_data'])||!in_array($input['sample_data'],array('0','1'),true))throw new InvalidArgumentException('حالت داده‌های نمونه را انتخاب کنید.');
        $out['admin_password']=$input['admin_password'];$out['sample_data']=$input['sample_data']==='1';return $out;
    }
    private function dsn($values){return 'mysql:host='.$values['db_host'].';port='.(int)$values['db_port'].';dbname='.$values['db_name'].';charset=utf8mb4';}
    private function fingerprint($values){return hash('sha256',$this->dsn($values).'|'.$values['db_user']);}
    private function connect($values)
    {
        return new PDO($this->dsn($values),$values['db_user'],$values['db_password'],array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>10,PDO::ATTR_EMULATE_PREPARES=>false));
    }
    private function checkEmpty($pdo,$values)
    {
        $tables=$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        if(!$tables)return;
        $path=$this->root.'/protected/runtime/install-state.json';$state=is_file($path)?json_decode(file_get_contents($path),true):null;
        if(!$state||!isset($state['database'])||!hash_equals($state['database'],$this->fingerprint($values)))throw new InvalidArgumentException('دیتابیس خالی نیست. برای جلوگیری از تغییر اطلاعات قبلی، یک دیتابیس خالی انتخاب کنید.');
    }
    public function checkDatabase($input)
    {
        if($this->installed())throw new RuntimeException('نصب‌کننده قفل است.');
        $this->phase='database';$values=$this->databaseValues($input);$pdo=$this->connect($values);$this->checkEmpty($pdo,$values);
        return 'اتصال برقرار است و دیتابیس برای نصب آماده است.';
    }
    private function atomicWrite($path,$contents)
    {
        $temp=$path.'.'.bin2hex(random_bytes(6)).'.tmp';$handle=@fopen($temp,'x');
        if(!$handle)throw new RuntimeException('ساخت فایل تنظیمات ممکن نشد.');
        try{if(fwrite($handle,$contents)!==strlen($contents))throw new RuntimeException('نوشتن فایل کامل نشد.');fflush($handle);}catch(Throwable $e){fclose($handle);@unlink($temp);throw $e;}finally{if(is_resource($handle))fclose($handle);}
        @chmod($temp,0600);if(!@rename($temp,$path)){@unlink($temp);throw new RuntimeException('ثبت فایل تنظیمات ممکن نشد.');}
    }
    public function install($input)
    {
        if($this->installed())throw new RuntimeException('نصب‌کننده قفل است.');
        foreach($this->requirements() as $check)if(!$check['ok'])throw new InvalidArgumentException('پیش‌نیاز آماده نیست: '.$check['label']);
        $values=$this->validate($input);$mutex=@fopen($this->root.'/protected/runtime/installing.lock','c');
        if(!$mutex||!flock($mutex,LOCK_EX|LOCK_NB)){if($mutex)fclose($mutex);throw new RuntimeException('یک نصب دیگر در حال اجرا است؛ کمی بعد دوباره بررسی کنید.');}
        $level=ob_get_level();ob_start();
        @set_time_limit(180);
        try{
            if($this->installed())throw new RuntimeException('نصب‌کننده قفل است.');
            $this->phase='database';$pdo=$this->connect($values);$this->checkEmpty($pdo,$values);
            $this->atomicWrite($this->root.'/protected/runtime/install-state.json',json_encode(array('database'=>$this->fingerprint($values),'started_at'=>date(DATE_ATOM))));
            $this->phase='schema';$schema=file_get_contents($this->root.'/database/mysql.sql');
            foreach(explode(';',$schema) as $statement)if(trim($statement)!=='')$pdo->exec($statement);
            unset($pdo);
            $config=require $this->root.'/protected/config/main.php';
            $config['components']['db']=array('connectionString'=>$this->dsn($values),'username'=>$values['db_user'],'password'=>$values['db_password'],'charset'=>'utf8mb4','emulatePrepare'=>false,'enableParamLogging'=>false);
            define('BAZAARCHE_INSTALL_CONFIG',$config);
            $this->migrate();
            $this->phase='sample';if($values['sample_data'])$this->seed();
            $this->phase='account';$db=Yii::app()->db;$tx=$db->beginTransaction();
            try{
                $hash=password_hash($values['admin_password'],PASSWORD_DEFAULT);
                $existing=$db->createCommand('SELECT id FROM users WHERE email=:email')->queryScalar(array(':email'=>$values['admin_email']));
                $admin=array('name'=>$values['admin_name'],'email'=>$values['admin_email'],'password_hash'=>$hash,'role'=>'superadmin','is_active'=>1);
                if($existing)$db->createCommand()->update('users',$admin,'id=:id',array(':id'=>$existing));else $db->createCommand()->insert('users',$admin);
                foreach(array('site_name'=>$values['site_name'],'site_description'=>$values['site_description'],'public_url'=>$values['site_url'],'contact_email'=>$values['admin_email'],'active_theme'=>'market-light','ai_api_enabled'=>'0','zarinpal_enabled'=>'0','footer_text'=>$values['site_description'],'announcement'=>$values['sample_data']?ShopSettings::get('announcement',''):'') as $key=>$value)ShopSettings::set($key,$value);
                foreach(array_keys(ThemeService::catalog()) as $key){$options=ThemeOptions::get($key);$options['hero_title']=$values['site_name'];$options['hero_text']=$values['site_description'];ShopSettings::set('theme_options_'.$key,CJSON::encode($options));}
                ShopSettings::set('installed_at',date('Y-m-d H:i:s'));$tx->commit();
            }catch(Exception $e){if($tx->active)$tx->rollback();throw $e;}
            $this->phase='configuration';
            if(!is_file($this->root.'/protected/config/security.key'))$this->atomicWrite($this->root.'/protected/config/security.key',bin2hex(random_bytes(32)));
            $local=array('components'=>array('db'=>$config['components']['db']));
            $source="<?php\n// Generated by the web installer. Keep this file private.\nif(getenv('SHOP_DSN'))return array();\nreturn ".var_export($local,true).";\n";
            $this->atomicWrite($this->root.'/protected/config/local.php',$source);
            // local.php also serves as a fail-closed lock if this marker cannot be written.
            try{$this->atomicWrite($this->root.'/protected/runtime/install.lock',json_encode(array('installed_at'=>date(DATE_ATOM),'samples'=>$values['sample_data'])));}
            catch(RuntimeException $e){error_log('Bazaarche installation completed; local.php is the installation lock.');}
            @unlink($this->root.'/protected/runtime/install-state.json');
            return array('cache_available'=>ShopSettings::get('cache_dependencies_ready','1')==='1');
        }finally{while(ob_get_level()>$level)ob_end_clean();flock($mutex,LOCK_UN);fclose($mutex);}
    }
    private function migrate(){require $this->root.'/tools/upgrade.php';}
    private function seed(){require $this->root.'/tools/seed.php';}
    public function errorMessage($exception)
    {
        if($exception instanceof InvalidArgumentException||$exception instanceof RuntimeException&&!($exception instanceof PDOException))return $exception->getMessage();
        error_log('Bazaarche installer failed during '.$this->phase.' ('.get_class($exception).', code '.(int)$exception->getCode().').');
        if($this->phase==='database')return 'اتصال دیتابیس برقرار نشد. آدرس، پورت، نام دیتابیس، کاربر و رمز آن را بررسی کنید؛ دیتابیس باید قبلاً در پنل هاست ساخته شده باشد.';
        if($this->phase==='schema')return 'ساخت یا ارتقای جداول انجام نشد. دسترسی‌های CREATE، ALTER، INDEX، SELECT، INSERT، UPDATE و DELETE کاربر دیتابیس را بررسی و دوباره تلاش کنید.';
        return 'نصب در مرحله «'.$this->phase.'» کامل نشد. پیش‌نیازها و دسترسی پوشه‌ها را بررسی کنید و با همان دیتابیس دوباره تلاش کنید.';
    }
}

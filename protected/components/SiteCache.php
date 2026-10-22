<?php
/** Yii cache storage with transaction-safe, database-owned dependency generations. */
class SiteCache extends CApplicationComponent
{
    private $storage;
    private $signature;

    public function backend()
    {
        $mode = ShopSettings::get('cache_backend', 'db');
        if ($mode === 'off') return null;
        $signature = $mode.'|'.Yii::app()->db->connectionString;
        if ($signature !== $this->signature) {
            $config = $mode === 'file'
                ? array('class'=>'CFileCache', 'cachePath'=>Yii::app()->runtimePath.'/site-cache')
                : array('class'=>'CDbCache', 'connectionID'=>'db', 'cacheTableName'=>'site_cache', 'autoCreateCacheTable'=>false);
            $config['keyPrefix'] = hash('sha256', Yii::app()->basePath.'|'.$signature);
            $this->storage = Yii::createComponent($config);
            $this->storage->init();
            $this->signature = $signature;
        }
        return $this->storage;
    }

    public function remember($key, $tables, $loader)
    {
        // Never read/write shared cache from a transaction's uncommitted snapshot.
        if (Yii::app()->db->currentTransaction) return call_user_func($loader);
        try {
            $storage = $this->backend();
            if ($storage) {
                $generation = $this->generation($tables);
                $cacheKey = hash('sha256', serialize(array('v1', $key, $generation)));
                $entry = $storage->get($cacheKey);
                if (is_array($entry) && array_key_exists('value', $entry)) return $entry['value'];
            }
        } catch (Exception $exception) {
            Yii::log('Cache unavailable: '.$exception->getMessage(), CLogger::LEVEL_WARNING, 'application.cache');
            return call_user_func($loader);
        }
        if (!$storage) return call_user_func($loader);
        $value = call_user_func($loader);
        try {
            // A writer may have committed while the loader was running.
            if ($generation === $this->generation($tables)) {
                $storage->set($cacheKey, array('value'=>$value), max(30, min(86400, (int)ShopSettings::get('cache_ttl', '600'))));
            }
        } catch (Exception $exception) {
            Yii::log('Cache write unavailable.', CLogger::LEVEL_WARNING, 'application.cache');
        }
        return $value;
    }

    private function generation($tables)
    {
        sort($tables);
        $rows = Yii::app()->db->createCommand()->select('scope,version')->from('cache_versions')
            ->where(array('in', 'scope', $tables))->order('scope')->queryAll();
        if (count($rows) !== count($tables)) throw new CException('Missing cache dependency migration.');
        return $rows;
    }

    /** Invalidation spans both storage backends and is transactional. */
    public function invalidateAll()
    {
        Yii::app()->db->createCommand('UPDATE cache_versions SET version=:version')->execute(array(':version'=>random_int(1000000000000,1000000000000000)));
    }

    public static function read($key, $tables, $loader)
    {
        $cache = Yii::app()->getComponent('siteCache');
        return $cache ? $cache->remember($key, $tables, $loader) : call_user_func($loader);
    }
}

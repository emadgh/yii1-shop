<?php
/** Media reads. The presentation cache lives only for the current PHP request. */
class MediaRepository
{
    public static function library($query='', $offset=0, $accept='*', $scope='all')
    {
        $types=array_map('trim',explode(',',$accept));
        if (!in_array($scope, array('all','catalogs'), true) || count($types)>10 || array_diff($types,array('*','image/*','image/jpeg','image/png','image/webp','application/pdf','text/plain'))) {
            throw new CHttpException(400,'فیلتر رسانه معتبر نیست.');
        }
        $command=Yii::app()->db->createCommand()->select('*')->from('media')->where('original_name LIKE :q',array(':q'=>'%'.mb_substr(trim($query),0,100).'%'));
        if ($scope==='catalogs') $command->andWhere('upload_scope=:scope',array(':scope'=>'catalogs'));
        if (!in_array('*',$types,true)) {
            if (in_array('image/*',$types,true)) $types=array_merge(array_diff($types,array('image/*')),array('image/jpeg','image/png','image/webp'));
            $command->andWhere(array('in','mime',array_values(array_unique($types))));
        }
        return $command->order('id DESC')->limit(48)->offset(max(0,(int)$offset))->queryAll();
    }

    private static $byId = array();
    private static $byFilename = array();

    public static function reset()
    {
        self::$byId = self::$byFilename = array();
    }

    public static function remember($rows)
    {
        foreach ($rows as $row) {
            self::$byId[(int)$row['id']] = $row;
            self::$byFilename[$row['filename']] = $row;
        }
    }

    /** Validation uses a fresh read, independent of the presentation cache. */
    public static function findMany($ids)
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) {
            return array();
        }
        $rows = Yii::app()->db->createCommand()->select('*')->from('media')
            ->where(array('in', 'id', $ids))->queryAll();
        self::remember($rows);
        $map = array();
        foreach ($rows as $row) {
            $map[(int)$row['id']] = $row;
        }
        return $map;
    }

    public static function forPicker($ids)
    {
        $missing = array();
        foreach ($ids as $id) {
            if (!array_key_exists((int)$id, self::$byId)) {
                $missing[] = (int)$id;
            }
        }
        if ($missing) {
            $found = self::findMany($missing);
            foreach ($missing as $id) {
                if (!isset($found[$id])) {
                    self::$byId[$id] = false;
                }
            }
        }
        $rows = array();
        foreach ($ids as $id) {
            if (!empty(self::$byId[(int)$id])) {
                $rows[] = self::$byId[(int)$id];
            }
        }
        return $rows;
    }

    public static function primeFilenames($filenames)
    {
        $missing = array();
        foreach (array_unique($filenames) as $filename) {
            if ($filename !== '' && !array_key_exists($filename, self::$byFilename)) {
                $missing[] = $filename;
            }
        }
        if (!$missing) {
            return;
        }
        $rows = Yii::app()->db->createCommand()->select('*')->from('media')
            ->where(array('in', 'filename', $missing))->queryAll();
        self::remember($rows);
        foreach ($missing as $filename) {
            if (!isset(self::$byFilename[$filename])) {
                self::$byFilename[$filename] = false;
            }
        }
    }

    public static function byFilename($filename)
    {
        self::primeFilenames(array($filename));
        return self::$byFilename[$filename] ?? false;
    }

    public static function find($id)
    {
        $rows = self::findMany(array($id));
        return $rows[(int)$id] ?? false;
    }

    public static function insert($data)
    {
        Yii::app()->db->createCommand()->insert('media', $data);
        return self::find(Yii::app()->db->getLastInsertID());
    }
}

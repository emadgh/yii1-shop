<?php
/** Compatibility facade: upload, reads and image formatting have separate owners. */
class MediaService
{
    public static function ingest($temp, $name)
    {
        return MediaUploadService::ingest($temp, $name);
    }

    public static function removeFiles($filename, $variants)
    {
        MediaUploadService::removeFiles($filename, $variants);
    }

    public static function find($id)
    {
        return MediaRepository::find($id);
    }

    public static function describe($row)
    {
        return MediaImage::describe($row);
    }

    public static function selected($ids, $imageOnly = true, $max = 20)
    {
        if (!is_array($ids)) {
            $ids = array_filter(explode(',', (string)$ids));
        }
        foreach ($ids as $id) {
            if (!is_scalar($id) || !ctype_digit(trim((string)$id)) || (int)$id < 1) {
                throw new Exception('رسانه انتخاب‌شده معتبر نیست.');
            }
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (count($ids) > $max) {
            throw new Exception('تعداد رسانه‌های انتخاب‌شده بیشتر از حد مجاز است.');
        }
        $found = MediaRepository::findMany($ids);
        $rows = array();
        foreach ($ids as $id) {
            $row = $found[$id] ?? false;
            if (!$row || ($imageOnly && strpos($row['mime'], 'image/') !== 0)) {
                throw new Exception('رسانه انتخاب‌شده معتبر نیست.');
            }
            $rows[] = $row;
        }
        return $rows;
    }

    public static function responsive($filename, $alt, $options = array())
    {
        return MediaImage::render($filename, $alt, $options, MediaRepository::byFilename($filename));
    }
}

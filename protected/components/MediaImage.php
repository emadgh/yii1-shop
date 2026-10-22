<?php
/** Pure formatting: media metadata is supplied by the caller, never fetched here. */
class MediaImage
{
    public static function describe($row)
    {
        $variants = json_decode($row['variants'], true) ?: array();
        $row['url'] = UploadService::url($row['filename']);
        $row['thumb'] = isset($variants['thumb']) ? UploadService::url($variants['thumb']['path']) : '';
        $row['variants'] = $variants;
        return $row;
    }

    public static function render($filename, $alt, $options, $metadata)
    {
        if ($metadata) {
            $variants = json_decode($metadata['variants'], true) ?: array();
            $sources = $seen = array();
            foreach ($variants as $variant) {
                if (isset($seen[$variant['width']])) {
                    continue;
                }
                $seen[$variant['width']] = true;
                $sources[] = UploadService::url($variant['path']).' '.$variant['width'].'w';
            }
            if ($sources) {
                $options['srcset'] = implode(', ', $sources);
                if (!isset($options['sizes'])) {
                    $options['sizes'] = '(max-width: 640px) 90vw, (max-width: 1000px) 45vw, 400px';
                }
            }
            $options['width'] = $metadata['width'];
            $options['height'] = $metadata['height'];
        }
        return CHtml::image(UploadService::url($filename), $alt, $options);
    }
}

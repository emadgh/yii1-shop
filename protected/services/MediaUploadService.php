<?php
/** Validates files, builds variants and cleans them up if metadata persistence fails. */
class MediaUploadService
{
    public static function ingest($temporaryFile, $name, $scope='general', $accept='*')
    {
        if (!in_array($scope, array('general','catalogs'), true) || !is_string($accept)) throw new Exception('بخش آپلود معتبر نیست.');
        $size = filesize($temporaryFile);
        if (!$size || $size > 10 * 1024 * 1024) {
            throw new Exception('حداکثر حجم فایل ۱۰ مگابایت است.');
        }
        $mime = mime_content_type($temporaryFile);
        $accepted=explode(',', $accept);
        if ($accept!=='*' && !in_array($mime,$accepted,true) && !(in_array('image/*',$accepted,true) && strpos($mime,'image/')===0)) throw new Exception('فرمت فایل برای این فیلد مجاز نیست.');
        $token = bin2hex(random_bytes(16));
        $directory = Yii::app()->params['uploadsDir'].'/media';
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw new Exception('ساخت پوشه رسانه ممکن نیست.');
        }
        $variants = array();
        $width = $height = 0;
        if (in_array($mime, array('image/jpeg', 'image/png', 'image/webp'), true)) {
            $info = @getimagesize($temporaryFile);
            if (!$info || $info[0] * $info[1] > 24000000) {
                throw new Exception('ابعاد تصویر معتبر نیست یا از ۲۴ مگاپیکسل بیشتر است.');
            }
            $image = @imagecreatefromstring(file_get_contents($temporaryFile));
            if (!$image) {
                throw new Exception('تصویر خوانده نشد.');
            }
            $width = $info[0];
            $height = $info[1];
            try {
                foreach (array('thumb' => 320, 'medium' => 800, 'large' => 1600) as $key => $maximum) {
                    $w = min($width, $maximum);
                    $h = max(1, (int)round($height * $w / $width));
                    $canvas = imagecreatetruecolor($w, $h);
                    imagealphablending($canvas, false);
                    imagesavealpha($canvas, true);
                    imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
                    imagecopyresampled($canvas, $image, 0, 0, 0, 0, $w, $h, $width, $height);
                    $relative = 'media/'.$token.'-'.$key.'.webp';
                    $saved = imagewebp($canvas, Yii::app()->params['uploadsDir'].'/'.$relative, 84);
                    imagedestroy($canvas);
                    if (!$saved) {
                        throw new Exception('ساخت WebP ناموفق بود.');
                    }
                    $variants[$key] = array('path' => $relative, 'width' => $w, 'height' => $h);
                }
            } catch (Exception $exception) {
                self::removeFiles('', $variants);
                imagedestroy($image);
                throw $exception;
            }
            imagedestroy($image);
            $filename = $variants['large']['path'];
            $mime = 'image/webp';
            $size = filesize(Yii::app()->params['uploadsDir'].'/'.$filename);
        } elseif (in_array($mime, array('application/pdf', 'text/plain'), true)) {
            if ($mime === 'text/plain' && preg_match('/<\?(?:php|=)|<script\b/i', file_get_contents($temporaryFile))) {
                throw new Exception('محتوای اجرایی در فایل متنی مجاز نیست.');
            }
            $filename = 'media/'.$token.'.'.($mime === 'application/pdf' ? 'pdf' : 'txt');
            if ($mime === 'application/pdf' && substr(file_get_contents($temporaryFile, false, null, 0, 5), 0, 5) !== '%PDF-') {
                throw new Exception('فایل PDF معتبر نیست.');
            }
            if (!copy($temporaryFile, Yii::app()->params['uploadsDir'].'/'.$filename)) {
                throw new Exception('ذخیره فایل ناموفق بود.');
            }
        } else {
            throw new Exception('فرمت مجاز: JPG، PNG، WebP، PDF و متن ساده.');
        }
        try {
            return MediaRepository::insert(array('filename' => $filename,
                'original_name' => mb_substr(basename($name), 0, 240), 'mime' => $mime,
                'size' => $size, 'width' => $width, 'height' => $height, 'upload_scope'=>$scope,
                'variants' => CJSON::encode($variants), 'created_at' => date('Y-m-d H:i:s')));
        } catch (Exception $exception) {
            self::removeFiles($filename, $variants);
            throw $exception;
        }
    }

    public static function removeFiles($filename, $variants)
    {
        $paths = array($filename);
        foreach ($variants as $variant) {
            $paths[] = $variant['path'];
        }
        foreach (array_unique($paths) as $path) {
            if (preg_match('~^media/[a-f0-9]{32}(?:-(?:thumb|medium|large))?\.(?:webp|pdf|txt)$~D', $path)) {
                @unlink(Yii::app()->params['uploadsDir'].'/'.$path);
            }
        }
    }
}

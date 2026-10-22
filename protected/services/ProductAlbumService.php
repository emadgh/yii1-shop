<?php
/** Album rules. ProductWriteService owns the surrounding transaction and lock. */
class ProductAlbumService
{
    public static function revision($productId)
    {
        return hash('sha256', CJSON::encode(ProductAlbumRepository::state($productId)));
    }

    /** Compatibility entry point for existing callers. */
    public static function editorRows($product)
    {
        return ProductAlbumQuery::editorRows($product);
    }

    public static function normalize($rows)
    {
        if (!is_array($rows) || count($rows) > 20) {
            throw new Exception('حداکثر ۲۰ آلبوم برای هر محصول مجاز است.');
        }
        $out = $seen = array();
        $total = 0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                throw new Exception('اطلاعات آلبوم معتبر نیست.');
            }
            foreach (array('id', 'title', 'layout', 'media_ids') as $key) {
                if (isset($row[$key]) && !is_scalar($row[$key])) {
                    throw new Exception('اطلاعات آلبوم معتبر نیست.');
                }
            }
            $id = (string)($row['id'] ?? '0');
            $title = trim((string)($row['title'] ?? ''));
            $layout = $row['layout'] ?? 'grid';
            $ids = trim((string)($row['media_ids'] ?? ''));
            if (!ctype_digit($id) || ((int)$id !== 0 && isset($seen[(int)$id]))) {
                throw new Exception('شناسه آلبوم نامعتبر یا تکراری است.');
            }
            $seen[(int)$id] = true;
            if ($title === '' || mb_strlen($title) > 150) {
                throw new Exception('عنوان هر آلبوم الزامی و حداکثر ۱۵۰ حرف است.');
            }
            if (!isset(ProductAlbum::layouts()[$layout])) {
                throw new Exception('سبک نمایش آلبوم معتبر نیست.');
            }
            if ($ids !== '' && !preg_match('/^[1-9][0-9]*(?:,[1-9][0-9]*)*$/D', $ids)) {
                throw new Exception('شناسه تصاویر آلبوم معتبر نیست.');
            }
            $media = MediaService::selected($ids, true, 100);
            $total += count($media);
            if ($total > 500) {
                throw new Exception('حداکثر ۵۰۰ تصویر برای هر محصول مجاز است.');
            }
            $out[] = array('id' => (int)$id, 'title' => $title, 'layout' => $layout,
                'media_ids' => $ids, 'media' => $media);
        }
        return $out;
    }

    public static function save($product, $rows, $revision)
    {
        $transaction = Yii::app()->db->currentTransaction;
        if (!$transaction || !$transaction->active) {
            throw new LogicException('Album save requires the product transaction.');
        }
        if ($revision !== null && (!is_string($revision) || !hash_equals(self::revision($product->id), $revision))) {
            throw new Exception('آلبوم‌های محصول پس از بازکردن فرم تغییر کرده‌اند؛ صفحه را تازه کنید.');
        }
        $existing = ProductAlbumRepository::ids($product->id);
        foreach ($rows as $row) {
            if ($row['id'] && !in_array($row['id'], $existing, true)) {
                throw new Exception('آلبوم انتخاب‌شده متعلق به این محصول نیست.');
            }
        }
        $imageCount = ProductAlbumRepository::replace($product, $rows, $existing);
        Audit::record('products', $product->id, 'albums', array('count' => count($rows), 'images' => $imageCount));
    }
}

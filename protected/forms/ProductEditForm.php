<?php
/** Submitted form state and normalization, independent of HTTP and persistence. */
class ProductEditForm extends CFormModel
{
    public $productAttributes = array();
    public $tagsText = '';
    public $specs = array();
    public $meta = array();
    public $albums = array();
    public $albumRevision = '';
    public $stockSnapshot = 0;
    public $editAlbums = false;
    public $mediaContext = '';

    public function load($post)
    {
        if (!isset($post['Product']) || !is_array($post['Product'])) {
            throw new CHttpException(400, 'اطلاعات محصول معتبر نیست.');
        }
        $this->productAttributes = $post['Product'];
        $this->mediaContext = $post['media_context'] ?? '';
        $this->tagsText = $post['tags'] ?? '';
        $this->specs = $post['spec_rows'] ?? array();
        $this->meta = $post['meta_rows'] ?? array();
        $this->stockSnapshot = $post['stock_snapshot'] ?? -1;
        $this->editAlbums = ($post['albums_present'] ?? '') === '1';
        if ($this->editAlbums) {
            $this->albums = $post['albums'] ?? array();
            $this->albumRevision = $post['album_revision'] ?? '';
        }
    }

    public function apply($product)
    {
        foreach ($this->productAttributes as $value) {
            if (!is_scalar($value) && $value !== null) {
                throw new Exception('اطلاعات محصول معتبر نیست.');
            }
        }
        if (!is_string($this->tagsText) || !is_scalar($this->stockSnapshot)) {
            throw new Exception('اطلاعات فرم معتبر نیست.');
        }
        $data = $this->productAttributes;
        foreach (array('price', 'compare_price') as $key) {
            $data[$key] = Money::parse(isset($data[$key]) && $data[$key] !== '' ? $data[$key] : '0');
        }
        if (($data['discount_type'] ?? '') === 'fixed') {
            $data['discount_value'] = Money::parse($data['discount_value'] ?? '0');
        }
        $product->attributes = $data;
        if ($product->parent_product_id === '') {
            $product->parent_product_id = null;
        }
        if ($product->category_id === '') {
            $product->category_id = null;
        }
        $product->featured = !empty($data['featured']) ? 1 : 0;
    }

    public static function pairs($rows)
    {
        if (!is_array($rows) || count($rows) > 100) {
            throw new Exception('حداکثر ۱۰۰ مشخصه مجاز است.');
        }
        $out = $seen = array();
        foreach ($rows as $row) {
            if (!is_array($row) || (isset($row['key']) && !is_scalar($row['key']))
                || (isset($row['value']) && !is_scalar($row['value']))) {
                throw new Exception('اطلاعات مشخصات معتبر نیست.');
            }
            $key = trim((string)($row['key'] ?? ''));
            $value = trim((string)($row['value'] ?? ''));
            if ($key === '' && $value === '') {
                continue;
            }
            if ($key === '' || mb_strlen($key) > 100 || mb_strlen($value) > 2000 || isset($seen[$key])) {
                throw new Exception('کلید خالی، تکراری یا مقدار بیش از حد بلند است.');
            }
            $seen[$key] = true;
            $out[] = array($key, $value);
        }
        return $out;
    }

    public function viewData()
    {
        return array('tagsText' => is_string($this->tagsText) ? $this->tagsText : '',
            'specs' => self::displayPairs($this->specs),
            'meta' => self::displayPairs($this->meta),
            'albums' => $this->albums, 'albumRevision' => $this->albumRevision,
            'stockSnapshot' => is_scalar($this->stockSnapshot) ? $this->stockSnapshot : -1);
    }

    private static function displayPairs($rows)
    {
        $safe = array();
        foreach (is_array($rows) ? $rows : array() as $row) {
            if (is_array($row)) {
                $safe[] = array('key' => isset($row['key']) && is_scalar($row['key']) ? (string)$row['key'] : '',
                    'value' => isset($row['value']) && is_scalar($row['value']) ? (string)$row['value'] : '');
            }
        }
        return $safe;
    }
}

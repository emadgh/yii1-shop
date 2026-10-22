<?php
if (PHP_SAPI !== 'cli') {
    exit(1);
}
$root = dirname(__DIR__);
$copy = $root.'/protected/runtime/architecture-'.bin2hex(random_bytes(5)).'.sqlite';
copy($root.'/protected/runtime/store.sqlite', $copy);
putenv('SHOP_DSN=sqlite:'.$copy);
require __DIR__.'/bootstrap.php';
$checks = 0;
function architectureCheck($ok, $label)
{
    global $checks;
    if (!$ok) {
        throw new Exception('FAILED '.$label);
    }
    $checks++;
    echo 'PASS '.$label."\n";
}
function architectureReject($operation, $label)
{
    try {
        $operation();
    } catch (Exception $exception) {
        architectureCheck(true, $label);
        return;
    }
    architectureCheck(false, $label);
}
function productInput($product, $overrides = array(), $extra = array())
{
    $input = new ProductEditForm;
    $input->load(array_merge(array('Product' => array_merge($product->attributes, $overrides),
        'stock_snapshot' => (int)$product->stock, 'tags' => '', 'spec_rows' => array(), 'meta_rows' => array()), $extra));
    return $input;
}
try {
    $violations = array();
    foreach (array('protected/views', 'protected/widgets') as $directory) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$directory, FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $code = '';
            foreach (token_get_all(file_get_contents($file->getPathname())) as $token) {
                if (is_array($token)) {
                    if (in_array($token[0], array(T_COMMENT, T_DOC_COMMENT, T_WHITESPACE, T_CONSTANT_ENCAPSED_STRING, T_INLINE_HTML), true)) {
                        continue;
                    }
                    $code .= $token[1];
                } else {
                    $code .= $token;
                }
            }
            if (preg_match('/->(?:createCommand|queryAll|queryRow|queryScalar|queryColumn)\(|::model\(/', $code)) {
                $violations[] = $file->getPathname();
            }
        }
    }
    architectureCheck(!$violations, 'views/widgets have no direct SQL or ActiveRecord queries: '.implode(', ', $violations));
    $controller = file_get_contents($root.'/protected/controllers/AdminProductController.php');
    architectureCheck(strpos($controller, 'beginTransaction') === false && strpos($controller, 'createCommand') === false,
        'product controller does not own SQL or transactions');

    $db = Yii::app()->db;
    $db->enableProfiling = true;
    $media = $db->createCommand("SELECT * FROM media WHERE mime LIKE 'image/%' ORDER BY id LIMIT 2")->queryAll();
    $ids = array((int)$media[1]['id'], (int)$media[0]['id']);
    MediaRepository::reset();
    $before = count(Yii::getLogger()->getProfilingResults(null, 'system.db.CDbCommand.query', true));
    $selected = MediaService::selected(array($ids[0], $ids[1], $ids[0]));
    architectureCheck(count(Yii::getLogger()->getProfilingResults(null, 'system.db.CDbCommand.query', true)) - $before === 1, 'media selection uses one query for multiple IDs');
    architectureCheck(array_map(function ($row) { return (int)$row['id']; }, $selected) === $ids,
        'batch selection retains requested order and removes duplicates');
    $before = count(Yii::getLogger()->getProfilingResults(null, 'system.db.CDbCommand.query', true));
    for ($i = 0; $i < 100; $i++) {
        MediaRepository::forPicker($ids);
        MediaRepository::byFilename($selected[0]['filename']);
    }
    architectureCheck(count(Yii::getLogger()->getProfilingResults(null, 'system.db.CDbCommand.query', true)) === $before, '100 repeated presentation lookups issue no further SQL');
    $db->createCommand()->delete('media', 'id=:id', array(':id' => $ids[0]));
    architectureReject(function () use ($ids) { MediaService::selected($ids); }, 'validation rejects deleted media despite a warm presentation cache');
    architectureReject(function () { MediaService::selected(array(array('bad'))); }, 'nested media IDs rejected without PHP warnings');

    $product = new Product;
    $input = productInput($product, array('title' => 'Architecture fixture', 'sku' => 'ARCH-FIXTURE',
        'slug' => 'architecture-fixture', 'price' => '20,000', 'stock' => 4,
        'status' => 'published', 'discount_type' => 'none', 'discount_value' => 0));
    architectureCheck(ProductWriteService::save($product, $input), 'write service creates a product independently of HTTP');
    architectureCheck((int)$db->createCommand('SELECT SUM(quantity) FROM inventory_movements WHERE product_id=:id')->queryScalar(array(':id' => $product->id)) === 4,
        'write service records opening stock in the same transaction');
    $persisted = Product::model()->findByPk($product->id);
    $badTags = implode(',', array_map(function ($i) { return 'tag'.$i; }, range(1, 31)));
    $input = productInput($persisted, array('title' => 'MUST ROLLBACK', 'stock' => 7), array('tags' => $badTags,
        'spec_rows' => array(array('key' => 'Unsaved', 'value' => 'Unsaved'))));
    architectureReject(function () use ($persisted, $input) { ProductWriteService::save($persisted, $input); },
        'dependent write failure rejects the complete product update');
    $fresh = Product::model()->findByPk($product->id);
    architectureCheck($fresh->title === 'Architecture fixture' && (int)$fresh->stock === 4,
        'rollback restores product title and stock');
    architectureCheck((int)$db->createCommand('SELECT SUM(quantity) FROM inventory_movements WHERE product_id=:id')->queryScalar(array(':id' => $fresh->id)) === 4
        && !ProductAttribute::model()->countByAttributes(array('product_id' => $fresh->id)),
        'rollback also restores inventory ledger and specifications');
    $input = productInput($fresh, array('title' => 'STALE'), array('stock_snapshot' => 3));
    architectureReject(function () use ($fresh, $input) { ProductWriteService::save($fresh, $input); }, 'write service preserves stock concurrency guard');
    ProductWriteService::delete(Product::model()->findByPk($product->id));
    architectureCheck(!Product::model()->findByPk($product->id), 'product deletion uses the same service boundary');
    echo "All $checks architecture checks passed on isolated database.\n";
} finally {
    Yii::app()->db->active = false;
    @unlink($copy);
}

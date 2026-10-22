<?php
/** The single transaction boundary for a product and its dependent records. */
class ProductWriteService
{
    public static function save($product, ProductEditForm $input)
    {
        $input->apply($product);
        $specs = ProductEditForm::pairs($input->specs);
        $metadata = ProductEditForm::pairs($input->meta);
        $albums = $input->editAlbums ? ProductAlbumService::normalize($input->albums) : array();
        if (!$product->validate()) {
            return false;
        }

        $transaction = TransactionScope::begin();
        $isNew = $product->isNewRecord;
        try {
            $oldStock = $isNew ? 0 : ProductRepository::lockStock($product->id);
            if (!$isNew && $oldStock !== (int)$input->stockSnapshot) {
                throw new Exception('موجودی پس از بازکردن فرم تغییر کرده است. صفحه را تازه و دوباره ویرایش کنید.');
            }
            if (!$product->save(false)) {
                throw new Exception('ذخیره ناموفق بود.');
            }
            $delta = (int)$product->stock - $oldStock;
            if ($delta || $isNew) {
                OrderService::movement($product->id, null, $delta, $isNew ? 'opening' : 'adjustment', 'ثبت از فرم محصول');
            }
            ProductRepository::replaceProperties($product->id, $specs, $metadata);
            TagService::sync('product', $product->id, $input->tagsText);
            if ($input->editAlbums) {
                ProductAlbumService::save($product, $albums, $isNew ? null : $input->albumRevision);
            }
            $transaction->commit();
            return true;
        } catch (Exception $error) {
            if ($transaction->active) {
                $transaction->rollback();
            }
            if ($isNew) {
                $product->isNewRecord = true;
                $product->primaryKey = null;
            }
            throw $error;
        }
    }

    public static function delete($product)
    {
        $transaction = TransactionScope::begin();
        try {
            ProductRepository::lockStock($product->id);
            if (Product::model()->countByAttributes(array('parent_product_id' => $product->id))) {
                throw new CHttpException(422, 'ابتدا تنوع‌های محصول را جدا یا حذف کنید.');
            }
            if (ProductRepository::hasActiveReservations($product->id)) {
                throw new Exception('محصول رزرو فعال دارد؛ ابتدا سفارش را تعیین تکلیف کنید.');
            }
            ProductRepository::delete($product);
            $transaction->commit();
        } catch (Exception $error) {
            if ($transaction->active) {
                $transaction->rollback();
            }
            throw $error;
        }
    }
}

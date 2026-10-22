<?php
class AdminProductController extends AdminController
{
    public function actionIndex()
    {
        $this->pageTitle = 'مدیریت محصولات';
        $this->render('//admin/products/index', array('lowStock' => ProductEditorQuery::lowStock()));
    }

    public function actionCreate()
    {
        $product = new Product;
        $product->discount_type = 'none';
        $product->discount_value = $product->price = $product->compare_price = $product->stock = 0;
        $this->edit($product);
    }

    public function actionUpdate($id)
    {
        $this->edit($this->load($id));
    }

    private function edit($product)
    {
        $input = ProductEditorQuery::form($product);
        $error = '';
        if (isset($_POST['Product'])) {
            try {
                $input->load($_POST);
                if (ProductWriteService::save($product, $input)) {
                    $this->flash('محصول ذخیره شد.');
                    $this->redirect(array('adminProduct/index'));
                    return;
                }
            } catch (Exception $exception) {
                $error = $exception->getMessage();
            }
        }
        $this->pageTitle = $product->isNewRecord ? 'محصول جدید' : 'ویرایش محصول';
        $this->render('//admin/products/form', array_merge($input->viewData(), array(
            'model' => $product, 'error' => $error,
            'categories' => Category::options(),
            'parentOptions' => ProductEditorQuery::parentOptions($product->id),
        )));
    }

    public function actionDelete($id)
    {
        $this->requirePost();
        try {
            ProductWriteService::delete($this->load($id));
            $this->flash('محصول حذف شد؛ رسانه‌ها در کتابخانه باقی ماندند.');
        } catch (CHttpException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            $this->flash($exception->getMessage(), 'error');
        }
        $this->redirect(array('adminProduct/index'));
    }

    private function load($id)
    {
        $product = Product::model()->findByPk($id);
        if (!$product) {
            $this->notFound();
        }
        return $product;
    }
}

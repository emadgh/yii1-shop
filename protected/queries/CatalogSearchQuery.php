<?php
/** Catalog criteria are shared read logic, independent of the HTTP controller. */
class CatalogSearchQuery
{
    public static function search($category = null, $tag = null, $q = null, $sort = 'new', $facet = array(), $min = null, $max = null)
    {
        foreach (array($category, $tag, $q, $sort, $min, $max) as $value) {
            if ($value !== null && !is_string($value)) {
                throw new CHttpException(400);
            }
        }
        $criteria = new CDbCriteria;
        $criteria->compare('t.status', 'published');
        $criteria->addCondition('t.parent_product_id IS NULL');
        $criteria->with = array('images', 'category');
        $criteria->together = false;
        $current = null;
        $categoryIds = array();
        if ($category) {
            $current = Category::model()->findByAttributes(array('slug' => $category));
            if (!$current) {
                throw new CHttpException(404, 'مورد درخواستی پیدا نشد.');
            }
            $categoryIds = Hierarchy::descendants(Hierarchy::rows('categories'), $current->id);
            $criteria->addInCondition('t.category_id', $categoryIds);
        }
        if ($q) {
            SearchService::products($criteria, $q);
        }
        if ($tag) {
            $found = Tag::model()->findByAttributes(array('slug' => $tag));
            if (!$found) {
                throw new CHttpException(404, 'مورد درخواستی پیدا نشد.');
            }
            $criteria->addCondition('EXISTS (SELECT 1 FROM product_tag pt WHERE pt.product_id=t.id AND pt.tag_id=:tagId)');
            $criteria->params[':tagId'] = $found->id;
        }
        $sale = Product::salePriceExpression();
        $orders = array('new' => 't.created_at DESC', 'price-asc' => $sale.' ASC', 'price-desc' => $sale.' DESC');
        $criteria->order = $orders[$sort] ?? $orders['new'];
        $facetOptions = self::facetOptions($categoryIds);
        self::applyFacets($criteria, $facet, $facetOptions);
        self::applyPrices($criteria, $sale, $min, $max);

        $provider = new CActiveDataProvider('Product', array('criteria' => $criteria, 'pagination' => array('pageSize' => 12)));
        $products = $provider->getData();
        CatalogQuery::primeProducts($products);
        return array('provider' => $provider, 'products' => $products,
            'categories' => Category::model()->findAll(array('order' => 'name')),
            'currentCategory' => $current,
            'childCategories' => $current ? CatalogQuery::categoryTiles($current->id) : array(),
            'facetOptions' => $facetOptions, 'facet' => $facet,
            'min' => $min, 'max' => $max, 'category' => $category, 'q' => $q, 'sort' => $sort, 'tag' => $tag);
    }

    private static function facetOptions($categoryIds)
    {
        $criteria = new CDbCriteria;
        $criteria->condition = "p.status='published'";
        if ($categoryIds) {
            $criteria->addInCondition('p.category_id', $categoryIds);
        }
        $rows = Yii::app()->db->createCommand()->selectDistinct('a.name,a.value')
            ->from('product_attributes a')->join('products p', 'p.id=a.product_id')
            ->where($criteria->condition, $criteria->params)->order('a.name,a.value')->limit(200)->queryAll();
        $options = array();
        foreach ($rows as $row) {
            if (mb_strlen($row['value']) < 100) {
                $options[$row['name']][$row['value']] = $row['value'];
            }
        }
        return $options;
    }

    private static function applyFacets($criteria, $facets, $options)
    {
        if (!is_array($facets) || count($facets) > 10) {
            throw new CHttpException(400);
        }
        foreach ($facets as $name => $value) {
            if ($value === '') {
                continue;
            }
            if (!is_string($value) || !isset($options[$name][$value])) {
                throw new CHttpException(400, 'فیلتر معتبر نیست.');
            }
            $index = count($criteria->params);
            $criteria->addCondition('EXISTS (SELECT 1 FROM product_attributes af WHERE af.product_id=t.id AND af.name=:fn'.$index.' AND af.value=:fv'.$index.')');
            $criteria->params[':fn'.$index] = $name;
            $criteria->params[':fv'.$index] = $value;
        }
    }

    private static function applyPrices($criteria, $expression, $min, $max)
    {
        foreach (array('min' => array('>=', $min), 'max' => array('<=', $max)) as $key => $filter) {
            list($operator, $value) = $filter;
            if ($value === null || $value === '') {
                continue;
            }
            try {
                $number = Money::parse($value);
            } catch (Exception $exception) {
                throw new CHttpException(400, 'بازه قیمت معتبر نیست.');
            }
            $criteria->addCondition($expression.' '.$operator.' :price_'.$key);
            $criteria->params[':price_'.$key] = $number;
        }
    }

    public static function suggestions($query)
    {
        if (!is_string($query) || mb_strlen(trim($query)) < 2) {
            return array();
        }
        $criteria = new CDbCriteria;
        $criteria->compare('t.status', 'published');
        $criteria->addCondition('t.parent_product_id IS NULL');
        SearchService::products($criteria, $query);
        $criteria->limit = 6;
        $criteria->order = 't.title';
        $items = array();
        foreach (Product::model()->findAll($criteria) as $product) {
            $items[] = array('title' => $product->title, 'url' => $product->link(),
                'price' => Commerce::showPrices() ? Money::format($product->salePrice()) : '');
        }
        return $items;
    }
}

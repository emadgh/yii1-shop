<?php
class HomePageQuery
{
    public static function sections()
    {
        $options = ThemeOptions::get();
        $sections = array();
        foreach (ThemeOptions::blocks() as $block) {
            if (!$block['is_enabled']) {
                continue;
            }
            $data = array('block' => $block, 'options' => $options);
            switch ($block['block_type']) {
                case 'hero':
                    $data['heroSrc'] = ThemeOptions::hero($options);
                    break;
                case 'categories':
                    $data['categories'] = CatalogQuery::categoryTiles();
                    break;
                case 'collection':
                    $collection = $block['collection_id'] ? ProductCollection::model()->findByAttributes(
                        array('id' => $block['collection_id'], 'is_active' => 1)
                    ) : null;
                    if ($block['collection_id'] && !$collection) {
                        continue 2;
                    }
                    $products = $collection ? $collection->products($block['item_limit']) : CatalogQuery::featured($block['item_limit']);
                    CatalogQuery::primeProducts($products);
                    $data += array('collection' => $collection, 'products' => $products);
                    break;
                case 'latest_products':
                case 'popular_products':
                case 'category_products':
                    $category=$block['block_type']==='category_products'?Category::model()->findByPk($block['category_id']??0):null;
                    if($block['block_type']==='category_products'&&!$category)continue 2;
                    $data['products']=ProductFeedQuery::products($block['block_type'],$block['item_limit'],$category?$category->id:null);
                    $data['title']=$category?$category->name:($block['block_type']==='latest_products'?'آخرین محصولات':'پربازدیدترین محصولات');
                    $data['moreUrl']=$category?array('shop/category','slug'=>$category->slug):($block['block_type']==='latest_products'?array('shop/index'):null);
                    break;
                case 'blog':
                    $posts = Post::model()->findAll(array('condition' => "status='published'",
                        'order' => 'created_at DESC', 'limit' => min(6, (int)$block['item_limit'])));
                    MediaRepository::primeFilenames(array_map(function ($post) { return $post->cover_image; }, $posts));
                    $data += array('posts' => $posts, 'title' => $block['title'] ?: 'از مجله بازارچه');
                    break;
                case 'banner':
                    $data['description'] = ShopSettings::get('site_description');
                    break;
            }
            $sections[] = $data;
        }
        return $sections;
    }
}

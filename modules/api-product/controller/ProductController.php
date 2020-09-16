<?php
/**
 * ProductController
 * @package api-product
 * @version 0.0.1
 */

namespace ApiProduct\Controller;

use Product\Model\Product;
use LibFormatter\Library\Formatter;
use ProductLastSeen\Library\Seen;
use ProductCategory\Model\ProductCategoryChain as PCChain;

class ProductController extends \Api\Controller
{
	public function indexAction(){
		if(!$this->app->isAuthorized())
            return $this->resp(401);

        $sort_by = [
            'stat'    => 'stat',
            'created' => 'created',
            'price'   =>'price_min'
        ];
        $sort = $this->req->getQuery('sort', 'created');
        if(!isset($sort_by[$sort]))
            $sort = 'created';
        $sort = $sort_by[$sort];

        $by = $this->req->getQuery('by', 'DESC');
        if(!in_array($by, ['ASC','DESC']))
            $by = 'DESC';

        list($page, $rpp) = $this->req->getPager();

        $std_filter = ['q','user'];
        if(module_exists('product-stat'))
            $std_filter[] = 'stat';
        if(module_exists('product-category'))
            $std_filter[] = 'category';

        $cond = $this->req->getCond($std_filter);
        $cond['status'] = 2;

        $p_min = $this->req->getQuery('pmin');
        $p_max = $this->req->getQuery('pmax');
        if($p_min || $p_max){
            if($p_min && !$p_max)
                $cond['price_min'] = ['__op', '>=', $p_min];
            elseif($p_max && !$p_min)
                $cond['price_min'] = ['__op', '<=', $p_max];
            else
                $cond['price_min'] = ['__between', $p_min, $p_max];
        }

        $products = [];

        if(!isset($cond['category'])){
            $products = Product::get($cond, $rpp, $page, [$sort=>($by=='ASC')]) ?? [];   
            $total    = Product::count($cond); 
        }else{
            $c_cond = [];
            $cats   = $cond['category'];
            unset($cond['category']);

            foreach($cond as $ckey => $cval)
                $c_cond['product.' . $ckey] = $cval;

            if(isset($c_cond['product.q'])){
                $c_cond['product.name'] = ['__like', $c_cond['product.q']];
                unset($c_cond['product.q']);
            }

            $c_cond['product_category'] = $cats;

            $total  = PCChain::count($c_cond);
            $chains = PCChain::get($c_cond, $rpp, $page, ['product.'.$sort=>($by=='ASC')]);
            if($chains){
                $product_ids = array_column($chains, 'product');
                $products = Product::get(['id'=>$product_ids], 0, 1, [$sort=>($by=='ASC')]);
            }
        }
        
        if($products){
        	$products = Formatter::formatMany('product', $products, ['user']);
        	foreach($products as &$product){
        		$product->content = null;
        		$product->meta    = null;
        		$product->gallery = [];
        	}
        }

        $this->resp(0, $products, null, [
            'meta' => [
                'page'  => $page,
                'rpp'   => $rpp,
                'total' => $total
            ]
        ]);
	}

	public function singleAction(){
		if(!$this->app->isAuthorized())
            return $this->resp(401);

        $identity = $this->req->param->identity;
        $product = Product::getOne(['id'=>$identity, 'status'=>2]);
        if(!$product)
        	$product = Product::getOne(['slug'=>$identity, 'status'=>2]);

        if(!$product)
        	return $this->show404();

        if(module_exists('product-last-seen') && $this->user->isLogin())
            Seen::add($this->user->id, $product->id);

        $fmt = ['user'];
        if(module_exists('product-category'))
        	$fmt[] = 'category';
        if(module_exists('product-collateral'))
        	$fmt[] = 'collateral';

        $product = Formatter::format('product', $product, $fmt);

        $this->resp(0, $product);
	}
}
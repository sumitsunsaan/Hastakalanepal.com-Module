<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductPresenter;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductListingPresenterFactory;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;

class CustomerViewedProducts extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'customerviewedproducts';
        $this->tab = 'front_office_features';
        $this->version = '2.0.3';
        $this->author = 'Sumit Dahal | Hastakala Nepal';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Customers Also Viewed');
        $this->description = $this->l('Displays products viewed by other customers');
        $this->ps_versions_compliancy = ['min' => '8.2.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install() 
            && $this->installDatabase()
            && $this->registerHook('displayFooterProduct')
            && $this->registerHook('actionProductSave')
            && $this->registerHook('header')
            && $this->registerHook('actionObjectProductDeleteAfter')
            && Configuration::updateValue('CVP_NUM_PRODUCTS', 6)
            && Configuration::updateValue('CVP_CAROUSEL', 1)
            && Configuration::updateValue('CVP_IN_STOCK', 0)
            && Configuration::updateValue('CVP_SORTING', 'views')
            && Configuration::updateValue('CVP_CACHE_TTL', 3600);
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallDatabase()
            && Configuration::deleteByName('CVP_NUM_PRODUCTS')
            && Configuration::deleteByName('CVP_CAROUSEL')
            && Configuration::deleteByName('CVP_IN_STOCK')
            && Configuration::deleteByName('CVP_SORTING')
            && Configuration::deleteByName('CVP_CACHE_TTL');
    }

    private function installDatabase()
    {
        $sql = [];
        $sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'customer_viewed` (
            `id_customer_viewed` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_product` INT(11) UNSIGNED NOT NULL,
            `id_product_viewed` INT(11) UNSIGNED NOT NULL,
            `views` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_customer_viewed`),
            INDEX `product_pair` (`id_product`, `id_product_viewed`),
            INDEX `date_upd` (`date_upd`)
        ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }
        return true;
    }

    private function uninstallDatabase()
    {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'customer_viewed`');
    }

    public function getContent()
    {
        $output = '';
        
        if (Tools::isSubmit('submitSettings')) {
            $this->postProcess();
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        return $output.$this->renderForm();
    }

    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    protected function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->submit_action = 'submitSettings';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    protected function getConfigForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Number of products'),
                        'name' => 'CVP_NUM_PRODUCTS',
                        'class' => 'fixed-width-xs',
                        'desc' => $this->l('Maximum products to display'),
                        'validate' => 'isUnsignedInt'
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Enable carousel'),
                        'name' => 'CVP_CAROUSEL',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1],
                            ['id' => 'active_off', 'value' => 0]
                        ]
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Show only in-stock'),
                        'name' => 'CVP_IN_STOCK',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1],
                            ['id' => 'active_off', 'value' => 0]
                        ]
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Sorting method'),
                        'name' => 'CVP_SORTING',
                        'options' => [
                            'query' => [
                                ['id' => 'views', 'name' => $this->l('Most Viewed')],
                                ['id' => 'random', 'name' => $this->l('Random')],
                                ['id' => 'newest', 'name' => $this->l('Newest Arrivals')]
                            ],
                            'id' => 'id',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Cache TTL'),
                        'name' => 'CVP_CACHE_TTL',
                        'class' => 'fixed-width-xs',
                        'suffix' => $this->l('seconds'),
                        'desc' => $this->l('Result caching duration (0 to disable)'),
                        'validate' => 'isUnsignedInt'
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save')
                ]
            ]
        ];
    }

    protected function getConfigFormValues()
    {
        return [
            'CVP_NUM_PRODUCTS' => Configuration::get('CVP_NUM_PRODUCTS', 6),
            'CVP_CAROUSEL' => Configuration::get('CVP_CAROUSEL', 1),
            'CVP_IN_STOCK' => Configuration::get('CVP_IN_STOCK', 0),
            'CVP_SORTING' => Configuration::get('CVP_SORTING', 'views'),
            'CVP_CACHE_TTL' => Configuration::get('CVP_CACHE_TTL', 3600)
        ];
    }

    public function hookDisplayFooterProduct($params)
    {
        if (!isset($params['product']->id)) {
            return;
        }

        $id_product = (int)$params['product']->id;
        $products = $this->getRelatedProducts($id_product);

        if (empty($products)) {
            return;
        }

        $this->context->smarty->assign([
            'products' => $products,
            'carousel' => Configuration::get('CVP_CAROUSEL'),
            'carousel_settings' => $this->getCarouselSettings()
        ]);

        return $this->display(__FILE__, 'views/templates/hook/product_footer.tpl');
    }

    protected function getRelatedProducts($id_product)
    {
        $cache_key = $this->name.'_'.(int)$id_product;
        if (Configuration::get('CVP_CACHE_TTL') > 0 && $cache = Cache::retrieve($cache_key)) {
            return unserialize($cache);
        }

        $num_products = (int)Configuration::get('CVP_NUM_PRODUCTS', 6);
        $in_stock = (bool)Configuration::get('CVP_IN_STOCK', false);
        $sorting = Configuration::get('CVP_SORTING', 'views');
        $assembler = new ProductAssembler($this->context);
        $presenterFactory = new ProductListingPresenterFactory();
        $presentationSettings = $presenterFactory->getPresentationSettings();
        $presenter = new ProductPresenter(
            new ImageRetriever($this->context->link),
            $this->context->link,
            new PriceFormatter(),
            new ProductColorsRetriever(),
            $this->context->getTranslator()
        );

        $sql = new DbQuery();
        $sql->select('cv.id_product_viewed, SUM(cv.views) as total_views');
        $sql->from('customer_viewed', 'cv');
        $sql->where('cv.id_product = '.(int)$id_product);
        
        if ($in_stock) {
            $sql->innerJoin('stock_available', 'sa', 'sa.id_product = cv.id_product_viewed AND sa.quantity > 0');
        }

        $sql->groupBy('cv.id_product_viewed');
        
        switch ($sorting) {
            case 'random':
                $sql->orderBy('RAND()');
                break;
            case 'newest':
                $sql->orderBy('cv.date_upd DESC');
                break;
            default:
                $sql->orderBy('total_views DESC');
        }

        $sql->limit($num_products * 2);

        $results = Db::getInstance()->executeS($sql);
        if (empty($results)) {
            return [];
        }

        $product_ids = array_column($results, 'id_product_viewed');
        $products = [];
        
        if (!empty($product_ids)) {
            $sql = new DbQuery();
            $sql->select('p.*, product_shop.*, pl.*, i.id_image, sa.quantity');
            $sql->from('product', 'p');
            $sql->join(Shop::addSqlAssociation('product', 'p'));
            $sql->leftJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = '.(int)$this->context->language->id);
            $sql->leftJoin('image', 'i', 'i.id_product = p.id_product AND i.cover = 1');
            $sql->leftJoin('stock_available', 'sa', 'sa.id_product = p.id_product AND sa.id_product_attribute = 0');
            $sql->where('p.id_product IN ('.implode(',', array_map('intval', $product_ids)).')');
            $sql->where('product_shop.active = 1');
            
            $products = Db::getInstance()->executeS($sql);
            $products = Product::getProductsProperties($this->context->language->id, $products);
        }

        if ($in_stock) {
            $products = array_filter($products, function($product) {
                return $product['quantity'] > 0;
            });
        }

        $products = array_slice($products, 0, $num_products);

        $assembler = new ProductAssembler($this->context);
        $presenterFactory = new ProductPresenterFactory($this->context);
        $presentationSettings = $presenterFactory->getPresentationSettings();
        $presenter = new ProductPresenter(
            new ImageRetriever($this->context->link),
            $this->context->link,
            new PriceFormatter(),
            new ProductColorsRetriever(),
            $this->context->getTranslator()
        );

        $products_for_template = [];
        foreach ($products as $product) {
            $products_for_template[] = $presenter->present(
                $presentationSettings,
                $assembler->assembleProduct($product),
                $this->context->language
            );
        }

        if (Configuration::get('CVP_CACHE_TTL') > 0) {
            Cache::store($cache_key, serialize($products_for_template), (int)Configuration::get('CVP_CACHE_TTL'));
        }

        return $products_for_template;
    }

    protected function getCarouselSettings()
    {
        return [
            'loop' => true,
            'margin' => 20,
            'nav' => true,
            'dots' => false,
            'responsive' => [
                0 => ['items' => 2],
                768 => ['items' => 3],
                992 => ['items' => 4],
                1200 => ['items' => 5]
            ]
        ];
    }

    public function hookHeader()
    {
        if ($this->context->controller->php_self === 'product') {
            $this->trackProductView((int)Tools::getValue('id_product'));
            
            if (Configuration::get('CVP_CAROUSEL')) {
                $this->context->controller->registerStylesheet(
                    'cvp-carousel',
                    'modules/'.$this->name.'/views/css/owl.carousel.css',
                    ['media' => 'all', 'priority' => 150]
                );
                $this->context->controller->registerJavascript(
                    'cvp-carousel',
                    'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js',
                    [
                        'position' => 'bottom',
                        'priority' => 150,
                        'server' => 'remote'
                    ]
                );
            }
        }
    }

    protected function trackProductView($id_product)
    {
        $recentlyViewed = isset($_COOKIE['recently_viewed']) ? 
            json_decode($_COOKIE['recently_viewed'], true) : [];

        if (!empty($recentlyViewed)) {
            foreach ($recentlyViewed as $viewed_product) {
                if ($viewed_product != $id_product) {
                    $this->updateViewCount($id_product, $viewed_product);
                }
            }
        }

        array_unshift($recentlyViewed, $id_product);
        $recentlyViewed = array_slice(array_unique($recentlyViewed), 0, 5);
        setcookie('recently_viewed', json_encode($recentlyViewed), time() + 604800, '/');
    }

    protected function updateViewCount($main_product, $related_product)
    {
        $sql = 'INSERT INTO '._DB_PREFIX_.'customer_viewed 
                (id_product, id_product_viewed, views, date_add, date_upd)
                VALUES ('.(int)$main_product.', '.(int)$related_product.', 1, NOW(), NOW())
                ON DUPLICATE KEY UPDATE views = views + 1, date_upd = NOW()';
        Db::getInstance()->execute($sql);
    }

    public function hookActionProductSave($params)
    {
        if (isset($params['product']->id)) {
            Cache::clean($this->name.'_'.(int)$params['product']->id);
        }
    }

    public function hookActionObjectProductDeleteAfter($params)
    {
        if (isset($params['object']->id)) {
            Db::getInstance()->delete('customer_viewed', 
                'id_product = '.(int)$params['object']->id.' OR id_product_viewed = '.(int)$params['object']->id
            );
        }
    }

    protected function getCacheId($name = null)
    {
        return parent::getCacheId($name).'|'.(int)$this->context->language->id;
    }
}
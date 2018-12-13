<?php
/*
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @version  Release: $Revision$
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use PrestaShop\PrestaShop\Adapter\Category\CategoryProductSearchProvider;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;

class Ps_Categoryproducts extends Module implements WidgetInterface
{
    protected $html;
    protected $templateFile;

    public function __construct()
    {
        $this->name = 'ps_categoryproducts';
        $this->author = 'PrestaShop';
        $this->version = '1.0.4';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Products in the same category', array(), 'Modules.Categoryproducts.Admin');
        $this->description = $this->trans('Adds a block on the product page that displays products from the same category.', array(), 'Modules.Categoryproducts.Admin');
        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);

        $this->templateFile = 'module:ps_categoryproducts/views/templates/hook/ps_categoryproducts.tpl';
    }

    public function install()
    {
        return (parent::install()
            && Configuration::updateValue('CATEGORYPRODUCTS_DISPLAY_PRICE', 1)
            && Configuration::updateValue('CATEGORYPRODUCTS_DISPLAY_PRODUCTS', 16)
            && $this->registerHook('displayFooterProduct')
            && $this->registerHook('actionProductAdd')
            && $this->registerHook('actionProductUpdate')
            && $this->registerHook('actionProductDelete')
        );
    }

    public function uninstall()
    {
        if (!parent::uninstall() ||
            !Configuration::deleteByName('CATEGORYPRODUCTS_DISPLAY_PRICE') ||
            !Configuration::deleteByName('CATEGORYPRODUCTS_DISPLAY_PRODUCTS')) {
            return false;
        }
        return true;
    }

    public function getContent()
    {
        $this->html = '';

        if (Tools::isSubmit('submitCross')) {
            $isValidDisplayPrice = Tools::getValue('CATEGORYPRODUCTS_DISPLAY_PRICE') === '0' || Tools::getValue('CATEGORYPRODUCTS_DISPLAY_PRICE') === '1';
            if (false === $isValidDisplayPrice) {
                $this->html .= $this->displayError($this->trans('Invalid value for display price.', array(), 'Modules.Categoryproducts.Admin'));
            }

            if ($isValidDisplayPrice) {
                Configuration::updateValue('CATEGORYPRODUCTS_DISPLAY_PRICE', Tools::getValue('CATEGORYPRODUCTS_DISPLAY_PRICE'));
                Configuration::updateValue('CATEGORYPRODUCTS_DISPLAY_PRODUCTS', (int) Tools::getValue('CATEGORYPRODUCTS_DISPLAY_PRODUCTS'));

                $this->_clearCache($this->templateFile);
                $this->html .= $this->displayConfirmation($this->trans('The settings have been updated.', array(), 'Admin.Notifications.Success'));
            }
        }

        $this->html .= $this->renderForm();

        return $this->html;
    }

    public function hookAddProduct($params)
    {
        return $this->clearCache($params);
    }

    public function hookUpdateProduct($params)
    {
        return $this->clearCache($params);
    }

    public function hookDeleteProduct($params)
    {
        return $this->clearCache($params);
    }

    private function clearCache($params)
    {
        $params = $this->getInformationFromConfiguration($params);

        if ($params) {
            $this->_clearCache($this->templateFile, $params['cache_id']);
        } else {
            $this->_clearCache($this->templateFile);
        }

        return;
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->trans('Settings', array(), 'Admin.Global'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->trans('Display products\' prices', array(), 'Modules.Categoryproducts.Admin'),
                        'desc' => $this->trans('Show the prices of the products displayed in the block.', array(), 'Modules.Categoryproducts.Admin'),
                        'name' => 'CATEGORYPRODUCTS_DISPLAY_PRICE',
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->trans('Enabled', array(), 'Admin.Global'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->trans('Disabled', array(), 'Admin.Global'),
                            )
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Number of product to display', array(), 'Modules.Categoryproducts.Admin'),
                        'desc' => $this->trans('Show the prices of the products displayed in the block.', array(), 'Modules.Categoryproducts.Admin'),
                        'name' => 'CATEGORYPRODUCTS_DISPLAY_PRODUCTS',
                        'class' => 'fixed-width-xs',
                    ),
                ),
                'submit' => array(
                    'title' => $this->trans('Save', array(), 'Admin.Actions'),
                ),
            ),
        );

        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get(
            'PS_BO_ALLOW_EMPLOYEE_FORM_LANG'
        ) : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCross';
        $helper->currentIndex = $this->context->link->getAdminLink(
                'AdminModules',
                false
            ).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        return array(
            'CATEGORYPRODUCTS_DISPLAY_PRICE' => Configuration::get('CATEGORYPRODUCTS_DISPLAY_PRICE'),
            'CATEGORYPRODUCTS_DISPLAY_PRODUCTS' => Configuration::get('CATEGORYPRODUCTS_DISPLAY_PRODUCTS'),
        );
    }

    public function getWidgetVariables($hookName = null, array $configuration = array())
    {
        $params = $this->getInformationFromConfiguration($configuration);

        if ($params) {

            $products = $this->getCategoryProducts($params['id_product'], $params['id_category']);

            if (!empty($products)) {
                return array(
                    'products' => $products,
                );
            }

        }

        return false;
    }

    public function renderWidget($hookName = null, array $configuration = array())
    {
        $params = $this->getInformationFromConfiguration($configuration);

        if ($params) {
            if ((int)Configuration::get('CATEGORYPRODUCTS_DISPLAY_PRODUCTS') > 0) {

                // Need variables only if this template isn't cached
                if (!$this->isCached($this->templateFile, $params['cache_id'])) {
                    if (!empty($params['id_category'])) {
                        $category = new Category($params['id_category']);
                    }

                    if (empty($category) || !Validate::isLoadedObject($category) || !$category->active) {
                        return false;
                    }

                    $variables = $this->getWidgetVariables($hookName, $configuration);

                    if (empty($variables)) {
                        return false;
                    }

                    $this->smarty->assign($variables);
                }

                return $this->fetch(
                    $this->templateFile,
                    $params['cache_id']
                );
            }
        }

        return false;
    }

    private function getCategoryProducts($idProduct, $idCategory)
    {
        $category = new Category($idCategory);
        $showPrice = (bool) Configuration::get('CATEGORYPRODUCTS_DISPLAY_PRICE');

        $searchProvider = new CategoryProductSearchProvider(
            $this->getTranslator(),
            $category
        );

        $context = new ProductSearchContext($this->context);

        $query = new ProductSearchQuery();

        $nProducts = (int) Configuration::get('CATEGORYPRODUCTS_DISPLAY_PRODUCTS') + 1; // +1 If current product is found

        $query
            ->setResultsPerPage($nProducts)
            ->setPage(1)
        ;

        $query->setSortOrder(SortOrder::random());

        $result = $searchProvider->runQuery(
            $context,
            $query
        );

        $assembler = new ProductAssembler($this->context);
        $presenterFactory = new ProductPresenterFactory($this->context);
        $presentationSettings = $presenterFactory->getPresentationSettings();
        $presenter = new ProductListingPresenter(
            new ImageRetriever(
                $this->context->link
            ),
            $this->context->link,
            new PriceFormatter(),
            new ProductColorsRetriever(),
            $this->context->getTranslator()
        );

        $productsForTemplate = array();

        $presentationSettings->showPrices = $showPrice;

        $products = $result->getProducts();

        foreach ($products as $rawProduct) {
            // Not duplicate current product
            if ($rawProduct['id_product'] !== $idProduct && count($productsForTemplate) < (int) Configuration::get('CATEGORYPRODUCTS_DISPLAY_PRODUCTS')) {
                $productsForTemplate[] = $presenter->present(
                    $presentationSettings,
                    $assembler->assembleProduct($rawProduct),
                    $this->context->language
                );
            }
        }

        return $productsForTemplate;
    }

    private function getInformationFromConfiguration($configuration)
    {
        if (empty($configuration['product'])) {
            return false;
        }

        $product = $configuration['product'];
        if ($product instanceof Product) {
            $product = (array) $product;
            $product['id_product'] = $product['id'];
        }

        $id_product = $product['id_product'];
        $id_category = (isset($configuration['category']->id) ? (int) $configuration['category']->id : (int) $product['id_category_default']);

        if (!empty($id_product) && !empty($id_category)) {

            $cache_id = 'ps_categoryproducts|'.$id_product.'|'.$id_category;

            return array(
                'id_product' => $id_product,
                'id_category' => $id_category,
                'cache_id' => $this->getCacheId($cache_id),
            );
        }

        return false;
    }
}

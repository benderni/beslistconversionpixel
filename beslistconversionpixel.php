<?php

if (!defined('_PS_VERSION_'))
    exit;

/**
 * Beslist Conversion Pixel
 *
 * @author Benny Van der Stee
 *
 * Class BeslistConversionPixel
 */
class BeslistConversionPixel extends Module
{
    private $_html = '';

    /**
     * Basic configuration prestashop modules
     */
    public function __construct()
    {
        $this->name = 'beslistconversionpixel';
        $this->tab = 'administration';
        $this->version = '2.2.1';
        $this->author = 'Benny Van der Stee';
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.6.1.0');

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Beslist Conversion Pixel');
        $this->description = $this->l(
            'Adding the Beslist conversion pixel to the order confirmation page.'
        );

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (Configuration::get("CONVERSION_PIXEL_TEST")) {
            $this->warning = $this->l('Beslist Conversion Pixel is configured for testing.');
        }
    }

    /**
     * Installation script
     *
     * @return bool
     */
    public function install()
    {
        if (!parent::install() || !$this->registerHook('orderConfirmation')) {
            return false;
        }

        Configuration::updateValue(
            'CONVERSION_PIXEL_TEST',
            1
        );
        Configuration::updateValue(
            'CONVERSION_PIXEL_IDENT',
            ''
        );
        Configuration::updateValue(
            'CONVERSION_CATEGORY_SKU',
            ''
        );
        Configuration::updateValue(
            'CONVERSION_CATEGORY_EAN',
            ''
        );
        Configuration::updateValue(
            'CONVERSION_PRODUCT_SKU',
            ''
        );
        Configuration::updateValue(
            'CONVERSION_PRODUCT_EAN',
            ''
        );

        return true;
    }

    /**
     * Uninstall module script
     *
     * @return mixed
     */
    public function uninstall()
    {
        if (!parent::uninstall()
            || !Configuration::deleteByName('CONVERSION_PIXEL_TEST')
            || !Configuration::deleteByName('CONVERSION_PIXEL_IDENT')
            || !Configuration::deleteByName('CONVERSION_CATEGORY_SKU')
            || !Configuration::deleteByName('CONVERSION_CATEGORY_EAN')
            || !Configuration::deleteByName('CONVERSION_PRODUCT_SKU')
            || !Configuration::deleteByName('CONVERSION_PRODUCT_EAN')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Configuration backend
     *
     * @return string
     */
    public function getContent()
    {
        if (Tools::isSubmit('btnSubmit'))
        {
            Configuration::updateValue(
                'CONVERSION_PIXEL_TEST',
                Tools::getValue('conversion_test')
            );
            Configuration::updateValue(
                'CONVERSION_PIXEL_IDENT',
                strval(Tools::getValue('conversion_ident'))
            );
            Configuration::updateValue(
                'CONVERSION_CATEGORY_SKU',
                strval(Tools::getValue('conversion_category_sku'))
            );
            Configuration::updateValue(
                'CONVERSION_CATEGORY_EAN',
                strval(Tools::getValue('conversion_category_ean'))
            );
            Configuration::updateValue(
                'CONVERSION_PRODUCT_SKU',
                strval(Tools::getValue('conversion_product_sku'))
            );
            Configuration::updateValue(
                'CONVERSION_PRODUCT_EAN',
                strval(Tools::getValue('conversion_product_ean'))
            );
            $this->_html .= $this->displayConfirmation($this->l('Settings updated'));
        }

        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    /**
     * Configuration form display function
     *
     * @return mixed
     */
    public function renderForm()
    {
        $id_lang = $this->context->language->id;

        $categoryCore = new CategoryCore();
        $categories = $categoryCore->getCategories($id_lang, true, false);

        $query = array(array('id' => 0, 'label' => 'none'));
        foreach ($categories as $category) {
            $query[] = array(
                'id' => $category['id_category'],
                'label' => $category['name']
            );
        }

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Shop ID'),
                        'name' => 'conversion_ident',
                        'desc' => $this->l('This is the Shop ID/Code you got from Beslist.')
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Mode'),
                        'name' => 'conversion_test',
                        'required' => true,
                        'is_bool' => true,
                        'class' => 'input-radio',
                        'values' => array(
                            array(
                                'id' => 'conversion_yes',
                                'value' => 1,
                                'label' => $this->l('Test')),
                            array(
                                'id' => 'conversion_no',
                                'value' => 0,
                                'label' => $this->l('Production')),
                        )
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'conversion_category_sku',
                        'label' => $this->l('Parent Categorie'),
                        'options' => array(
                            'query' => $query,
                            'id' => 'id',
                            'name' => 'label'
                        ),
                        'desc' => $this->l('Selecteer de hoofd categorie van producten die onder volgende categorieën hangen: Computers, Electronica, Huishoudelijk apparatuur, Fietsen, Gereedschap en Software.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Unique Product ID'),
                        'name' => 'conversion_product_sku',
                        'desc' => $this->l('Unieke Code voor: Computers, Electronica, Huishoudelijk apparatuur, Fietsen, Gereedschap en Software. (vb. sku, upc, ...)')
                    ),
                    array(
                        'type' => 'select',
                        'name' => 'conversion_category_ean',
                        'label' => $this->l('Parent Categorie'),
                        'options' => array(
                            'query' => $query,
                            'id' => 'id',
                            'name' => 'label'
                        ),
                        'desc' => $this->l('Selecteer de hoofd categorie van producten die onder volgende categorieën hangen: Boeken, Engelse Boeken, CD’s, DVD’s en Games.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Unique Product ID'),
                        'name' => 'conversion_product_ean',
                        'desc' => $this->l('Unieke Code voor: Boeken, Engelse Boeken, CD’s, DVD’s en Games. (vb. ean, ean13, ...)')
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save')
                )
            )
        );

        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'uri' => $this->getPathUri(),
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $id_lang
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        $fields = array();

        $fields['conversion_ident'] = Configuration::get('CONVERSION_PIXEL_IDENT');
        $fields['conversion_test'] = Configuration::get('CONVERSION_PIXEL_TEST');
        $fields['conversion_category_sku'] = Configuration::get('CONVERSION_CATEGORY_SKU');
        $fields['conversion_category_ean'] = Configuration::get('CONVERSION_CATEGORY_EAN');
        $fields['conversion_product_sku'] = Configuration::get('CONVERSION_PRODUCT_SKU');
        $fields['conversion_product_ean'] = Configuration::get('CONVERSION_PRODUCT_EAN');

        return $fields;
    }

    /**
     * Variables to add on the beslistconversionpixel.tpl file.
     *
     * @param $params
     * @return mixed
     */
    public function hookOrderConfirmation($params)
    {
        global $smarty;

        $categorySku = Configuration::get('CONVERSION_CATEGORY_SKU');
        $categoryEan = Configuration::get('CONVERSION_CATEGORY_EAN');
        $productSku = Configuration::get('CONVERSION_PRODUCT_SKU');
        $productEan = Configuration::get('CONVERSION_PRODUCT_EAN');

        $order = $params['objOrder'];
        $products = $order->getProducts();
        $counter = 0;
        $productListing = array();
        foreach ($products as $product) {
            $categories = ProductCore::getProductCategories($product['product_id']);
            $unique = $productSku;
            if (in_array($categoryEan, $categories))
                $unique = $productEan;
            var_dump($unique);
            $counter++;
            $price = round(($product['product_price'] * (1 + ($product['tax_rate'] / 100))), 2);
            $id = !empty($product[$unique]) ? $product[$unique]:$product['product_id'];
            $productListing[] = array('id' => $id,'qty' => $product['product_quantity'], 'price' => $price);
        }
        $totalAmount = $order->total_paid - $order->total_shipping;

        $smarty->assign(
            array(
                'orderId' => $order->id,
                'orderSum' => $totalAmount,
                'orderCost' => $order->total_shipping,
                'productListing' => $productListing,
                'test' => Configuration::get('CONVERSION_PIXEL_TEST'),
                'ident' => Configuration::get('CONVERSION_PIXEL_IDENT'),
            )
        );

        return $this->display(__FILE__, 'beslistconversionpixel.tpl');
    }
}

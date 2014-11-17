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
    /**
     * Basic configuration prestashop modules
     */
    public function __construct()
    {
        $this->name = 'beslistconversionpixel';
        $this->tab = 'administration';
        $this->version = '2';
        $this->author = 'Benny Van der Stee';
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.6.0.9');

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
        $output = '';

        if (Tools::isSubmit('submit'.$this->name))
        {
            Configuration::updateValue(
                'CONVERSION_PIXEL_TEST',
                Tools::getValue('conversion_test')
            );
            Configuration::updateValue(
                'CONVERSION_PIXEL_IDENT',
                strval(Tools::getValue('conversion_ident'))
            );
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        return $output.$this->renderForm();
    }

    /**
     * Configuration form display function
     *
     * @return mixed
     */
    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Conversion test ?'),
                        'name' => 'conversion_ident',
                        'desc' => $this->l('Shop ID (Beslist Code)')
                    ),
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Conversion test: '),
                        'name' => 'conversion_test',
                        'required' => false,
                        'is_bool' => true,
                        'class' => 'input-radio',
                        'values' => array(
                            array(
                                'id' => 'conversion_yes',
                                'value' => 1,
                                'label' => $this->l('Yes')),
                            array(
                                'id' => 'conversion_no',
                                'value' => 0,
                                'label' => $this->l('No')),
                        )
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save')
                )
            )
        );

        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table =  $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit'.$this->name;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'uri' => $this->getPathUri(),
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        $fields = array();

        $fields['conversion_ident'] = Configuration::get('CONVERSION_PIXEL_IDENT');
        $fields['conversion_test'] = Configuration::get('CONVERSION_PIXEL_TEST');

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

        $order = $params['objOrder'];
        $products = $order->getProducts();
        $counter = 0;
        $productListing = array();
        foreach ($products as $product) {
            $counter++;
            $price = round(($product['product_price'] * (1 + ($product['tax_rate'] / 100))) * 100);
            $productListing[] = array('id' => $product['product_id'],'qty' => $product['product_quantity'], 'price' => $price);
        }
        $totalAmount = $order->total_paid - $order->total_shipping;

        $smarty->assign(
            array(
                'orderId' => $order->id,
                'orderSum' => $totalAmount * 100,
                'orderCost' => $order->total_shipping * 100,
                'productListing' => $productListing,
                'test' => Configuration::get('CONVERSION_PIXEL_TEST'),
                'ident' => Configuration::get('CONVERSION_PIXEL_IDENT'),
            )
        );

        return $this->display(__FILE__, 'beslistconversionpixel.tpl');
    }
}

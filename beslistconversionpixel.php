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
        $this->version = '1.1';
        $this->author = 'Benny Van der Stee';
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.6.0.6');

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

        return true;
    }

    /**
     * Uninstall module script
     *
     * @return mixed
     */
    public function uninstall()
    {
        if (!parent::uninstall()) {
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
        $output = null;

        if (Tools::isSubmit('submit'.$this->name))
        {
            Configuration::updateValue(
                'CONVERSION_PIXEL_TEST',
                (Tools::getValue('conversion_test')),
                false,
                null,
                (int)Context::getContext()->shop->id
            );
            Configuration::updateValue(
                'CONVERSION_PIXEL_IDENT',
                strval(Tools::getValue('conversion_ident')),
                false,
                null,
                (int)Context::getContext()->shop->id
            );
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        return $output.$this->displayForm();
    }

    /**
     * Configuration form display function
     *
     * @return mixed
     */
    public function displayForm()
    {
        // Get default Language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $fields_form = $this->initFieldsFormArray();

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
                array(
                    'desc' => $this->l('Save'),
                    'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                        '&token='.Tools::getAdminTokenLite('AdminModules'),
                ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        // Load current value
        $helper->fields_value['conversion_ident'] = Configuration::get('CONVERSION_PIXEL_IDENT', null, null, (int)Context::getContext()->shop->id);
        $helper->fields_value['conversion_test'] = Configuration::get('CONVERSION_PIXEL_TEST', null, null, (int)Context::getContext()->shop->id);

        return $helper->generateForm($fields_form);
    }

    /**
     * Create form fields
     *
     * @return mixed
     */
    private function initFieldsFormArray()
    {
        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Conversion test ?'),
                    'name' => 'conversion_ident',
                    'size' => 40,
                    'length' => 255,
                    'required' => false,
                    'desc' => $this->l('Leave empty for default.'),
                ),
                array(
                    'type' => 'radio',
                    'label' => $this->l('Conversion test: '),
                    'name' => 'conversion_test',
                    'class' => 't',
                    'required' => false,
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'conversion_yes',
                            'value' => 1,
                            'label' => $this->l('Yes')),
                        array(
                            'id' => 'conversion_no',
                            'value' => 0,
                            'label' => $this->l('No')),
                    ),
                    'desc' => $this->l('Use for testing or real conversions'),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'button'
            )
        );

        return $fields_form;
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
        $totalProducts = count($products);
        $counter = 0;
        $productListing = '';
        foreach ($products as $product) {
            $counter++;
            $price = round(($product['product_price'] * (1 + ($product['tax_rate'] / 100))) * 100);
            $productListing .=
                $product['product_id']
                . ':' . $product['product_quantity']
                . ':' . $price;

            if ($counter < $totalProducts) {
                $productListing .= ';';
            }
        }
        $totalAmount = $order->total_paid - $order->total_shipping;

        $smarty->assign(
            array(
                'orderId' => $order->id,
                'orderSum' => $totalAmount * 100,
                'orderCost' => $order->total_shipping * 100,
                'productListing' => $productListing,
                'test' => Configuration::get('CONVERSION_PIXEL_TEST', null, null, (int)Context::getContext()->shop->id),
                'ident' => Configuration::get('CONVERSION_PIXEL_IDENT', null, null, (int)Context::getContext()->shop->id),
            )
        );

        return $this->display(__FILE__, 'beslistconversionpixel.tpl');
    }
}

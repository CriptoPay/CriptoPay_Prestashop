<?php
/**
 * 2007-2016 Cripto-Pay.com
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to support@cripto-pay.com so we can send you a copy immediately.
 *
 *  @author    CriptoPay SL <soporte@cripto-pay.com>
 *  @copyright 2007-2016 CriptoPay SL
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  @version 3.2.2
 *  @source https://github.com/CriptoPay/CriptoPay_Prestashop
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_.'/criptopay/class/criptopay_orders.php';

class Criptopay extends PaymentModule
{

    public function __construct()
    {
        $this->name = 'criptopay';
        $this->tab = 'payments_gateways';
        $this->version = '3.2.1';
        $this->author = 'Cripto-Pay.com';
        $this->need_instance = 1;
        $this->module_key = '14bca04069a772275e398ee05d66939c';
        
        $this->controllers = array('payment', 'validation');
        $this->is_eu_compatible = 1;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Cripto-Pay.com');
        $this->description = $this->l('Accept Payments with Bitcoin and Blockahin currencies. ');


        $this->confirmUninstall = $this->l('Are you sure you want to stop accept payments?');

        $this->limited_countries = array('ES','EN');

        $this->limited_currencies = array('EUR','USD','XBT','GBP');
        
        $this->ps_versions_compliancy = array('min' => '1.4', 'max' => '1.6.99.99');
        
        if (Configuration::get('CRIPTOPAY_API_USER', false) == false ||
                Configuration::get('CRIPTOPAY_API_PASSWORD', false) == false) {
                $this->warning = $this->l('API User or API Password not configured!!! ');
        }
        
        /* Backward Compatibily */
        if (_PS_VERSION_ < '1.5') {
            require(_PS_MODULE_DIR_.$this->name.'/backward_compatibility/backward.php');
        }
        
    }

    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        $iso_code = Country::getIsoById(Configuration::get('PS_COUNTRY_DEFAULT'));

        if (in_array($iso_code, $this->limited_countries) == false) {
            $this->_errors[] = $this->l('This module is not available in your country');
            return false;
        }

        Configuration::updateValue('CRIPTOPAY_LIVE_MODE', false);

        include(dirname(__FILE__).'/sql/install.php');
        
        if (!Configuration::get('CRIPTOPAY_OS_PROCESING')) {
            $order_state = new OrderState();
            $order_state->name = array();
            foreach (Language::getLanguages() as $language) {
                if (Tools::strtolower($language['iso_code']) == 'es') {
                    $order_state->name[$language['id_lang']] = 'Procesando pago Cripto-Pay.com';
                } else {
                    $order_state->name[$language['id_lang']] = 'Payment procesing Cripto-Pay.com';
                }
            }
            $order_state->send_email = false;
            $order_state->color = '#FF9900';
            $order_state->hidden = false;
            $order_state->delivery = false;
            $order_state->logable = false;
            $order_state->invoice = false;

            if ($order_state->add()) {
                $source = dirname(__FILE__).'/views/img/os/CRIPTOPAY_OS_PROCESING.gif';
                $destination = dirname(__FILE__).'/../../img/os/'.(int) $order_state->id.'.gif';
                copy($source, $destination);
            }
            Configuration::updateValue('CRIPTOPAY_OS_PROCESING', (int) $order_state->id);
        }

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('payment') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('displayPaymentEU');
    }

    public function uninstall()
    {
        Configuration::deleteByName('CRIPTOPAY_API_USER');
        Configuration::deleteByName('CRIPTOPAY_API_PASSWORD');
        Configuration::deleteByName('CRIPTOPAY_API_CERT_PUB');
        Configuration::deleteByName('CRIPTOPAY_API_CERT_PRIV');
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitCriptopayModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCriptopayModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'CRIPTOPAY_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-user"></i>',
                        'desc' => $this->l('ID user of API'),
                        'name' => 'CRIPTOPAY_API_USER',
                        'label' => $this->l('API User'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-key"></i>',
                        'desc' => $this->l('Password assigned to your API user'),
                        'name' => 'CRIPTOPAY_API_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                    array(
                        'type' => 'file',
                        'name' => 'CRIPTOPAY_API_CERT_PUB',
                        'label' => $this->l('API Public certificate')."<br>".
                        Configuration::get('CRIPTOPAY_API_CERT_PUB', null),
                    ),
                    array(
                        'type' => 'file',
                        'name' => 'CRIPTOPAY_API_CERT_PRIV',
                        'label' => $this->l('API Private certificate')."<br>".
                        Configuration::get('CRIPTOPAY_API_CERT_PRIV', null),
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'CRIPTOPAY_LIVE_MODE' => Configuration::get('CRIPTOPAY_LIVE_MODE', null),
            'CRIPTOPAY_API_USER' => Configuration::get('CRIPTOPAY_API_USER', null),
            'CRIPTOPAY_API_PASSWORD' => Configuration::get('CRIPTOPAY_API_PASSWORD', null),
            'CRIPTOPAY_API_CERT_PUB' => Configuration::get('CRIPTOPAY_API_CERT_PUB', null),
            'CRIPTOPAY_API_CERT_PRIV' => Configuration::get('CRIPTOPAY_API_CERT_PRIV', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        unset($form_values['CRIPTOPAY_API_CERT_PUB']);
        unset($form_values['CRIPTOPAY_API_CERT_PRIV']);
        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
        
        if (!empty($_FILES)) {
            if (!is_dir(_PS_MODULE_DIR_.'/criptopay/certificados')) {
                $res = mkdir(_PS_MODULE_DIR_.'/criptopay/certificados', 0755, true);
                if (!$res) {
                    throw new Exception(
                        $this->l(
                            "Can't create directory criptopay/certificados,"
                            . " it's necesary that create it manually, contact with your administrator."
                        ));
                }
                copy(_PS_MODULE_DIR_.'/criptopay/index.php', _PS_MODULE_DIR_.'/criptopay/certificados/index.php');
            }
            foreach ($_FILES as $key => $file) {
                if (!empty($file['name'])) {
                    if($finfo->file($file['tmp_name']) == "text/plain" &&
                    (substr($file['name'],-3) == "crt" || substr($file['name'],-3) == "key") ){
                        $move = move_uploaded_file($file['tmp_name'],__DIR__.'/certificados/'.$file['name']);
                        Configuration::updateValue($key, $file['name']);
                    }else{
                        throw new Exception("Formato del fichero ".$file['name']." incorrecto ". $finfo->file($file['tmp_name']." "));
                    }
                }
            }
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    /**
     * This method is used to render the payment button,
     * Take care if the button should be displayed or not.
     */
    public function hookPayment($params)
    {
        if (!$this->active) {
                return;
        }

        $this->smarty->assign('module_dir', $this->_path);
        
        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    /**
     * This hook is used to display the order confirmation page.
     */
    public function hookPaymentReturn($params)
    {
        if ($this->active == false) {
            return;
        }
        
        $order = $params['objOrder'];

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {
            $this->smarty->assign('status', 'ok');
        }

        $this->smarty->assign(array(
            'id_order' => $order->id,
            'reference' => $order->reference,
            'params' => $params,
            'total' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
        ));

        return $this->display(__FILE__, 'views/templates/hook/confirmation.tpl');
    }
    
    public function hookDisplayPaymentEU($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $payment_options = array(
            'cta_text' => $this->l('Pay by CriptoPay'),
            'logo' => Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/logo.png'),
            'action' => $this->context->link->getModuleLink($this->name, 'payment', array(), true)
        );

        return $payment_options;
    }
    
    public function validateOrder(
        $id_cart,
        $id_order_state,
        $amount_paid,
        $payment_method = 'Unknown',
        $message = null,
        $transaction = array(),
        $currency_special = null,
        $dont_touch_amount = false,
        $secure_key = false,
        Shop $shop = null
    ) {
        if ($this->active) {
            if (version_compare(_PS_VERSION_, '1.5', '<')) {
                parent::validateOrder(
                    (int) $id_cart,
                    (int) $id_order_state,
                    (float) $amount_paid,
                    $payment_method,
                    $message,
                    $transaction,
                    $currency_special,
                    $dont_touch_amount,
                    $secure_key
                );
            } else {
                parent::validateOrder(
                    (int) $id_cart,
                    (int) $id_order_state,
                    (float) $amount_paid,
                    $payment_method,
                    $message,
                    $transaction,
                    $currency_special,
                    $dont_touch_amount,
                    $secure_key,
                    $shop
                );
            }
            CriptoPayOrders::saveOrder($this->currentOrder, $transaction['id_pago']);
            CriptoPayOrders::updateOrder($this->currentOrder, $transaction['estado']);
        }
    }
}

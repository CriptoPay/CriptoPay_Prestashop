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
 *  @version 3.2
 *  @source https://github.com/CriptoPay/CriptoPay_Prestashop
 */

require_once _PS_MODULE_DIR_.'/criptopay/class/criptopay_orders.php';

require_once _PS_MODULE_DIR_.'/criptopay/CriptoPayApi/CriptoPayApiRest.php';
require_once _PS_MODULE_DIR_.'/criptopay/CriptoPayApi/Excepcion.php';
require_once _PS_MODULE_DIR_.'/criptopay/CriptoPayApi/Log.php';

class CriptopayPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;
    public $display_column_right = false;

    public function postProcess()
    {
        $cart = $this->context->cart;
        $customer = new Customer((int)$cart->id_customer);
        // Divisa del carrito
        //$id_currency = intval(Configuration::get('PS_CURRENCY_DEFAULT'));
        $currency = new Currency($cart->id_currency);
        // Total de la compra
        $cantidad = $cart->getOrderTotal(true);
        // El número de pedido es  los 8 ultimos digitos del ID del carrito + el tiempo MMSS.
        //$numpedido = str_pad($cart->id, 8, "0", STR_PAD_LEFT) . date("is");
        $pago = array(
            "total" => (float)$cantidad, // Obligatorio
            "divisa" => $currency->iso_code,      //Obligatorio
            "concepto" => "Pago ".Context::getContext()->shop->name, //Obligatorio
            "URL_OK" => $this->context->link->getModuleLink(
                'criptopay',
                'validation',
                array('secure_key'=>$customer->secure_key),
                true
            ),
            "URL_KO" => $this->context->link->getPageLink('order', null, null, 'step=3')
        );
        
        if (Configuration::get('CRIPTOPAY_LIVE_MODE', true)) {
            $url = "https://api.cripto-pay.com";
        } else {
            $url = "https://testnet.cripto-pay.com";
        }
        define('CP_DEBUG', true);
        $CRIPTOPAY = new CriptoPayApiRest(
            Configuration::get('CRIPTOPAY_API_USER', null),
            Configuration::get('CRIPTOPAY_API_PASSWORD', null),
            _PS_MODULE_DIR_."/criptopay/certificados/",
            $url
        );
        //Agregamos los parámetros a la consulta
        $CRIPTOPAY->Set($pago);
        //Ejecutamos la funciíon degeneración
        $respuesta = $CRIPTOPAY->Get("PAGO", "GENERAR");
        //Verificamos que el id exista
        if (isset($respuesta->idpago)) {
            $customer = new Customer($cart->id_customer);
            
            $sql = 'INSERT INTO `'._DB_PREFIX_.'criptopay_orders`(
                `id_cart`,`id_pago`,`secure_key`)VALUES(
                \''.(int)$cart->id.'\' , \''.pSQL($respuesta->idpago).'\',\''.pSQL($this->context->customer->secure_key).'\')';
            Db::getInstance()->Execute($sql);
            
            Tools::redirect($CRIPTOPAY->getServidor().'/pago/'.$respuesta->idpago);
        } else {
            Tools::redirect('http://'.$_SERVER['HTTP_HOST'].__PS_BASE_URI__.'modules/criptopay/errorpago');
        }
        var_dump(error_get_last());
    }
}

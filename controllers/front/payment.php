<?php
/**
* 20014-2016 Cripto-Pay.com
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to support@cripto-paay.com so we can send you a copy immediately.
*
*  @author    CriptoPay SL <support@cripto-pay.com>
*  @copyright 20014-2016 CriptoPay SL
*  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of CriptoPay SL [ES]
*/

class CriptopayPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;
    public $display_column_right = false;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        //parent::initContent();
        $cart = $this->context->cart;
        // Divisa del carrito
        $id_currency = intval(Configuration::get('PS_CURRENCY_DEFAULT'));
        $currency = new Currency(intval($id_currency));
        // Total de la compra
        $cantidad = $cart->getOrderTotal(true, 3);
        // El número de pedido es  los 8 ultimos digitos del ID del carrito + el tiempo MMSS.
        $numpedido = str_pad($cart->id, 8, "0", STR_PAD_LEFT) . date("is");

        $pago = array(
            "total" => (float)$cantidad, // Obligatorio
            "divisa" => $currency->iso_code,      //Obligatorio
            "concepto" => "Pago ".$numpedido, //Obligatorio
            "URL_OK" => 'http://'.$_SERVER['HTTP_HOST'].__PS_BASE_URI__.'modules/criptopay/validation', //Opcionales
            "URL_KO" => 'http://'.$_SERVER['HTTP_HOST'].__PS_BASE_URI__.'modules/criptopay/errorpago' //Opcionales
        );

        require_once __DIR__.'/../../CriptoPayApiRest.php';

        //Instancia del Objeto para realizar la acciones
        $CRIPTOPAY = new CriptoPayApiRest($this->module->usuario_id,$this->module->usuario_password,__DIR__."/../../certificados/",$this->module->urltpv);
        //Agregamos los parámetros a la consulta
        $CRIPTOPAY->Set($pago);

        //Ejecutamos la funciíon degeneración
        $respuesta = $CRIPTOPAY->Get("PAGO","GENERAR");

        //Verificamos que el id exista
        if(isset($respuesta->idpago)){
            $customer = new Customer($cart->id_customer);
            //$this->module->validateOrder((int)$cart->id, Configuration::get('CRIPTOPAY_ORDER_ENVIADA'), $cantidad, $this->module->displayName, NULL, NULL, (int)$currency->id, false, $customer->secure_key);
            
            $sql = "INSERT INTO `"._DB_PREFIX_."criptopay_orders`(
                `id_cart`,`id_pago`,`id_order`,`id_customer`)VALUES(
                '".$cart->id."','".$respuesta->idpago."','".$this->module->currentOrder."','".$this->context->customer->id."')";
            Db::getInstance()->Execute($sql);
            
            Tools::redirect($CRIPTOPAY->GetServidor().'/pago/'.$respuesta->idpago);
        }else{
            Tools::redirect('http://'.$_SERVER['HTTP_HOST'].__PS_BASE_URI__.'modules/criptopay/errorpago');
        }
    }
}


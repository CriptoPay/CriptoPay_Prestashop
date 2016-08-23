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

class CriptopayValidationModuleFrontController extends ModuleFrontController
{
    /**
     * This class should be use by your Instant Payment
     * Notification system to validate the order remotely
     */
    public function postProcess()
    {
        
        if ((Tools::isSubmit('idpago') == false) || (Tools::isSubmit('secure_key') == false)) {
            $this->errors[] = $this->module->l('Los códigos seguros no han sido transmitidos');
            return $this->setTemplate('error.tpl');
        }
        
        $transaction = CriptoPayOrders::getOrderByIdPago(Tools::getValue('idpago'));

        $Cart = new Cart((int)$transaction['id_cart']);
        if ($Cart->OrderExists())
        {
            $estado = $this->getEstadoPago(Tools::getValue('idpago'));
            CriptoPayOrders::updateOrder($transaction['id_order'],$estado);
            Tools::redirect("index.php?controller=order-confirmation");
        }        
        $Customer = new Customer((int)$Cart->id_customer);
        
        if (Tools::getValue('secure_key') != $Customer->secure_key) {
            $this->errors[] = $this->module->l('Los códigos seguros son inválidos');
            return $this->setTemplate('error.tpl');
        }
        
        if ($Cart->id_customer == 0 ||
                $Cart->id_address_delivery == 0 ||
                $Cart->id_address_invoice == 0 ||
                !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        
        Context::getContext()->cart = $Cart;
        Context::getContext()->customer = $Customer;
        Context::getContext()->currency = new Currency((int)Context::getContext()->cart->id_currency);
        Context::getContext()->language = new Language((int)Context::getContext()->customer->id_lang);

        $estadopago = $this->getEstadoPago(Tools::getValue('idpago'));
        $transaction['estado'] = $estadopago;
        
        if ($estadopago >= 30) {
            $payment_status = Configuration::get('PS_OS_PAYMENT');
            $message = $this->module->l('El pago ha sido recibido y completado');
        } elseif ($estadopago >= 20) {
            $payment_status = Configuration::get('CRIPTOPAY_OS_PROCESING');
            $message = $this->module->l('El pago ha sido recibido por CriptoPay pero aún no está confirmado');
        } else {
            $payment_status = Configuration::get('PS_OS_ERROR');
            $message = $this->module->l('Ohh Ohh! Estado servidor: '.$estadopago);
        }

        $amount = (float)$Cart->getOrderTotal(true, Cart::BOTH);
                $this->module->validateOrder(
                $Cart->id,
                $payment_status,
                $amount,
                $this->module->displayName,
                $message,
                $transaction,
                (int)Context::getContext()->currency->id,
                false,
                $Customer->secure_key
            );
        Tools::redirect(
            'index.php?controller=order-confirmation&id_cart='.$Cart->id.
            '&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$Customer->secure_key
        );
    }

    protected function getEstadoPago($idpago)
    {
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
        $CRIPTOPAY->Set(array("idpago"=>$idpago));

        $respuesta = $CRIPTOPAY->Get("PAGO", "DETALLE");
        if ($respuesta->estado < 20) {
            //Si está incompleto o no pagado retornamos a la pasarela de pago
            Tools::redirect($CRIPTOPAY->getServidor().'/pago/'.$respuesta->idpago);
        }
        CriptoPayOrders::updatePago($idpago, $respuesta->estado, $respuesta->recibido->divisa, $respuesta->recibido->cantidad);
        return $respuesta->estado;
    }
}

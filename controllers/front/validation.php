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


class CriptopayValidationModuleFrontController extends ModuleFrontController{
	public function postProcess()
	{
		$cart = $this->context->cart;

		if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
			Tools::redirect('index.php?controller=order&step=1');

		// Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
		$authorized = false;
		foreach (Module::getPaymentModules() as $module)
			if ($module['name'] == 'criptopay')
			{
				$authorized = true;
				break;
			}

		if (!$authorized)
			die($this->module->l('This payment method is not available.', 'validation'));

		$customer = new Customer($cart->id_customer);

		if (!Validate::isLoadedObject($customer))
			Tools::redirect('index.php?controller=order&step=1');
                
            $sql = new DbQuery();
            $sql->select('*');
            $sql->from('criptopay_orders', 'c');
            $sql->where('c.id_customer = '.$this->context->customer->id);
            $CriptopayOrdenes = Db::getInstance()->executeS($sql);
            if(count($CriptopayOrdenes)<= 0){
                Tools::redirect('index.php?controller=order&step=1');
            }
        require_once __DIR__.'/../../CriptoPayApiRest.php';

        //Instancia del Objeto para realizar la acciones
        $CRIPTOPAY = new CriptoPayApiRest($this->module->usuario_id,$this->module->usuario_password,__DIR__."/../../certificados/",$this->module->urltpv);
        
        foreach ($CriptopayOrdenes as $Orden){
            $CRIPTOPAY->Set("idpago",$Orden['id_pago']);
            $EstadoPago = $CRIPTOPAY->Get("PAGO", "ESTADO");
            var_dump($EstadoPago);
            if($EstadoPago->estado >= 20){
                $sql = "DELETE FROM `"._DB_PREFIX_."criptopay_orders` WHERE `id_pago` = '".$Orden['id_pago']."'";
                Db::getInstance()->Execute($sql);
                $cart = $this->context->cart;
                $cantidad = $cart->getOrderTotal(true, 3);
                $this->module->validateOrder((int)$cart->id, _PS_OS_PAYMENT_, $cantidad, $this->module->displayName, NULL, NULL, (int)$currency->id, false, $customer->secure_key);
		Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$Orden["id_cart"].'&id_module='.(int)$this->module->id.'&id_order='.$Orden["id_order"]);                
            }elseif($EstadoPago->estado == 10){
                header('Location: http://'.$_SERVER['HTTP_HOST'].__PS_BASE_URI__.'modules/criptopay/parcial');
            }else{
                Tools::redirect($CRIPTOPAY->GetServidor().'/pago/'.$EstadoPago->_id);        
            }
        }
    }
}

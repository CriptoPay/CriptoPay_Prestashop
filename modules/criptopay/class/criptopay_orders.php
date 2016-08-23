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
 *  @version 3.2.1
 *  @source https://github.com/CriptoPay/CriptoPay_Prestashop
 */

if (!defined('_PS_VERSION_')) {
    exit;
}
class CriptoPayOrders
{
    public static function getOrderById($id_order)
    {
        return Db::getInstance()->getRow(
            'SELECT * FROM `'._DB_PREFIX_.'criptopay_orders`
			WHERE `id_order` = '.(int) $id_order
        );
    }

    public static function getOrderByIdPago($id_pago)
    {
        $sql = 'SELECT *
			FROM `'._DB_PREFIX_.'criptopay_orders`
			WHERE `id_pago` = \''.pSQL($id_pago).'\'';

        return Db::getInstance()->getRow($sql);
    }

    public static function saveOrder($id_order, $id_pago)
    {
        $sql = 'UPDATE `'._DB_PREFIX_.'criptopay_orders`
			SET `id_order` = \''.(int) $id_order.'\'
			WHERE `id_pago` = \''.pSQL($id_pago).'\'';

        Db::getInstance()->Execute($sql);
    }

    public static function updateOrder($id_order, $estado)
    {
        if ($estado >= 30) {
            $payment_status = Configuration::get('PS_OS_PAYMENT');
            //$message = $this->module->l('El pago ha sido recibido y completado');
        } elseif ($estado >= 20) {
            $payment_status = Configuration::get('CRIPTOPAY_OS_PROCESING');
            //$message = $this->module->l('El pago ha sido recibido por CriptoPay pero aún no está confirmado');
        } else {
            $payment_status = Configuration::get('PS_OS_ERROR');
            //$message = $this->module->l('Ohh Ohh! Estado servidor: '.$estadopago);
        }
        
        $Order = new OrderCore($id_order);

        if ($Order->current_state != $payment_status) {
            $Order->setCurrentState($payment_status);
            $Order->save();
        }
        
        $sql = 'UPDATE `'._DB_PREFIX_.'criptopay_orders`
			SET `estado` = \''.(int) $estado.'\'
			WHERE `id_order` = \''.(int) $id_order.'\'';
        Db::getInstance()->Execute($sql);
    }
    
    public static function updatePago($id_pago, $estado, $divisa, $cantidad)
    {
        $sql = 'UPDATE `'._DB_PREFIX_.'criptopay_orders`
			SET `estado` = \''.(int) $estado.'\',
                        `divisa` = \''.(string) $divisa.'\',
                        `cantidad` = \''.(float) $cantidad.'\'
			WHERE `id_pago` = \''.(string) $id_pago.'\'';
        Db::getInstance()->Execute($sql);
    }
}

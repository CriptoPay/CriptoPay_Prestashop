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

include_once dirname(__FILE__).'/../../config/config.inc.php';
include_once _PS_MODULE_DIR_.'/criptopay/criptopay.php';

if (Tools::getValue('apiuser') == Configuration::get('CRIPTOPAY_API_USER')) {
    if (Tools::getIsset('cifrados')) { //Array with idpago actualizados.
        
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
        $dclaros = $CRIPTOPAY->Desencriptar(Tools::getValue('apiuser'));
        $actualizaciones = Tools::jsonDecode($dclaros, true);
        foreach ($actualizaciones as $idpago => $estado) {
            CriptoPayOrders::updateOrder(CriptoPayOrders::getOrderByIdPago($idpago));
        }
    }
}

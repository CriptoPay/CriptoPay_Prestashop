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

function upgrade_module_3_2_1($object, $install = false)
{
    if (!Db::getInstance()->Execute('
            ALTER TABLE `'._DB_PREFIX_.'criptopay_orders` ADD `divisa` VARCHAR(5) NOT null,
            ADD `cantidad` FLOAT(8,6) NOT null
            ')) {
        throw new Exception("Error al actualizar tabla de ordenes");
        return false;
    }
    
    return true;
}
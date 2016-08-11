{*
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
 *}

<div class="row">
    <div class="col-xs-12">
        <p class="payment_module" id="criptopay_payment_button">
            <a href="{$link->getModuleLink('criptopay', 'payment', array(), true)|escape:'htmlall':'UTF-8'}" title="{l s='Pay with cripto-pay.com' mod='criptopay'}">
                    {l s='Pay with criptocurrencies' mod='criptopay'}
                    <span>{l s='Bitcoin & Altcoins' mod='criptopay'}</span>
            </a>
        </p>
    </div>
</div>

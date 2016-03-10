{*
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
*}
<div class="row">
	<div class="col-xs-12">
		<p class="payment_module">
			<a href="{$link->getModuleLink('criptopay', 'payment', [], true)|escape:'html'}" title="{l s='Pagar con Bitcoin & Altcoins' mod='criptopay'}" id="CriptoPayLogoTipoUnico" class="CriptoPayLogoTipoPago cheque">
				{l s='Cripto-Pay: Pagar con Bitcoin & Altcoins' mod='criptopay'} <span>{l s='(el pago es instantáneo)' mod='criptopay'}</span>
			</a>
		</p>
	</div>
</div>

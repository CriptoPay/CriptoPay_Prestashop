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


<div>
	<h3>{l s='Redirect your customer' mod='criptopay'}:</h3>
	<ul class="alert alert-info">
			<li>{l s='This action should be used to redirect your customer to the website of your payment processor' mod='criptopay'}.</li>
	</ul>
	
	<div class="alert alert-warning">
		{l s='You can redirect your customer with an error message' mod='criptopay'}:
		<a href="{$link->getModuleLink('criptopay', 'redirect', ['action' => 'error'], true)|escape:'htmlall':'UTF-8'}" title="{l s='Look at the error' mod='criptopay'}">
			<strong>{l s='Look at the error message' mod='criptopay'}</strong>
		</a>
	</div>
	
	<div class="alert alert-success">
		{l s='You can also redirect your customer to the confirmation page' mod='criptopay'}:
		<a href="{$link->getModuleLink('criptopay', 'confirmation', ['cart_id' => $cart_id, 'secure_key' => $secure_key], true)|escape:'htmlall':'UTF-8'}" title="{l s='Confirm' mod='criptopay'}">
			<strong>{l s='Go to the confirmation page' mod='criptopay'}</strong>
		</a>
	</div>
</div>

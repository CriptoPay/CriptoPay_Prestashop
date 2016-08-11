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


<div class="panel">
	<div class="row criptopay-header">
		<img src="{$module_dir|escape:'html':'UTF-8'}views/img/Logo_Horizontal.png" class="col-xs-6 col-md-4 text-center" id="payment-logo" />
		<div class="col-xs-6 col-md-4 text-center">
			<h4>{l s='Bitcoin & Blockchain Payments Gateway' mod='criptopay'}</h4>
			<h4>{l s='Fast - Secure - Reliable' mod='criptopay'}</h4>
		</div>
		<div class="col-xs-12 col-md-4 text-center">
			<a href="https://cripto-pay.com/panel?utm_source=criptopay_admin&utm_medium=modulo&utm_content=registro&utm_campaign=prestashop" target="_blank" class="btn btn-primary" id="create-account-btn">{l s='Create an account now!' mod='criptopay'}</a><br />
			{l s='Already have an account?' mod='criptopay'}<a href="https://cripto-pay.com/panel?utm_source=criptopay_admin&utm_medium=modulo&utm_content=login&utm_campaign=prestashop" target="_blank"> {l s='Log in' mod='criptopay'}</a>
		</div>
	</div>

	<hr />
	
	<div class="criptopay-content">
		<div class="row">
			<div class="col-md-6">
				<h5>{l s='CriptoPay is the easiest way to accpet bitcoin in a ecommerce' mod='criptopay'}</h5>
				<dl>
					<dt>&middot; {l s='Increase customer payment options' mod='criptopay'}</dt>
					
					<dt>&middot; {l s='Without ChargeBack' mod='criptopay'}</dt>
					
					<dt>&middot; {l s='Enhanced security' mod='criptopay'}</dt>
					
					<dt>&middot; {l s='Instantaneous' mod='criptopay'}</dt>
				</dl>
			</div>
			
			<div class="col-md-6">
			</div>
		</div>

		<hr />
		
		<div class="row">
			<div class="col-md-12">
				<h4>{l s='Accept payments in any place with a minimum commision' mod='criptopay'}</h4>
				
				<div class="row">
                                    <div class="col-md-8">
					<img src="{$module_dir|escape:'html':'UTF-8'}views/img/divisas/bitcoin.png" id="payment-logo" height="75px"/>
                                        <img src="{$module_dir|escape:'html':'UTF-8'}views/img/divisas/dogecoin.png" id="payment-logo" height="75px"/>
                                        <img src="{$module_dir|escape:'html':'UTF-8'}views/img/divisas/litecoin.png" id="payment-logo" height="75px"/>
                                    </div>
					<div class="col-md-4">
						<p class="text-branded">{l s='Contact with us if you need more help' mod='criptopay'}  <a href="mailto:suppor@cripto-pay.com?subject=PrestashopModule">support@cripto-pay.com</a></p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

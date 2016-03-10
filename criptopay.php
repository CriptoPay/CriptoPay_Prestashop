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
if (!defined('_CAN_LOAD_FILES_'))
	exit;

class criptopay extends PaymentModule
{
	private	$_html = '';
	private $_postErrors = array();

	public function __construct(){
		$this->name = 'criptopay';
		$this->tab = 'payments_gateways';
		$this->version = '3.0';
		$this->author = 'Cripto-Pay.com';
		$this->controllers = array('payment', 'validation', 'errorpago');
		// Array config con los datos de configuraciÃ³n
		$config = Configuration::getMultiple(array('CRIPTOPAY_SANDBOX', 'CRIPTOPAY_TESTNET',
                    'CRIPTOPAY_USUARIO_ID', 'CRIPTOPAY_USUARIO_PASSWORD'));
		// Establecer propiedades segÃºn los datos de configuraciÃ³n
                /**
                 * Entornos
                 * 0->ProducciÃ³n
                 * 1->Sandbox (Autoconfirmaciones)
                 * 3,4->Testnet
                 */
                $this->env = 0;
                if($config['CRIPTOPAY_SANDBOX']){
                    $this->env += 1;
                }
                if($config['CRIPTOPAY_TESTNET']){
                    $this->env += 3;
                }
		switch($this->env){
			case 0: //ProducciÃ³n
				$this->urltpv = "https://api.cripto-pay.com";
				break;
			case 1: //Sandbox (Autoconfirmaciones)
				$this->urltpv = "https://sandbox.cripto-pay.com";
				break;
                        case 3: //Testnet
			case 4: //Testnet
				$this->urltpv = "https://apitestnet.cripto-pay.com";
				break;
		}
                $this->urltpv = 'https://apitest.cripto-pay.com';
		if (isset($config['CRIPTOPAY_USUARIO_ID']))
			$this->usuario_id = $config['CRIPTOPAY_USUARIO_ID'];
		if (isset($config['CRIPTOPAY_USUARIO_PASSWORD']))
			$this->usuario_password = $config['CRIPTOPAY_USUARIO_PASSWORD'];
                
		if (isset($config['CRIPTOPAY_SANDBOX'])){
                    $this->sandbox = $config['CRIPTOPAY_SANDBOX'];
                }else{
                    $this->sandbox = 0;
                }
		if (isset($config['CRIPTOPAY_TESTNET'])){
			$this->testnet = $config['CRIPTOPAY_TESTNET'];
                }else{
                    $this->testnet = 0;
                }

		parent::__construct();

		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('CriptoPay');
		$this->description = $this->l('Aceptar pagos con Bitcoin & Altcoins');

		// Mostrar aviso en la página principal de mÃ³dulos si faltan datos de configuración.
		if (!isset($this->urltpv)
		OR !isset($this->usuario_id)
		OR !isset($this->usuario_password)
		OR !isset($this->testnet)
		OR !isset($this->sandbox))
		$this->warning = $this->l('Faltan datos por configurar del mod. CriptoPay.');
	}

	public function install()
	{
		// Valores por defecto al instalar el mÃ“dulo
		if (!parent::install()
			OR !Configuration::updateValue('CRIPTOPAY_TESTNET', '0')
                        OR !Configuration::updateValue('CRIPTOPAY_SANDBOX', '0')
			OR !Configuration::updateValue('CRIPTOPAY_NOMBRE', $this->l('Escriba el nombre de su tienda'))
			OR !Configuration::updateValue('CRIPTOPAY_USUARIO_ID',0)
			OR !Configuration::updateValue('CRIPTOPAY_USUARIO_PASSWORD', 0)
			OR !$this->registerHook('payment')
			OR !$this->registerHook('paymentReturn')
                        OR !$this->registerHook('header')){
			return false;
                    }
                
                $sql= "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."criptopay_orders`(
	    `id_criptopay` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	    `id_cart` INT(11) NOT NULL,
            `id_pago` VARCHAR(50) NOT NULL,
            `id_order` INT(11) NOT NULL,
            `id_customer` INT(11) NOT NULL)";
	   
	    if(!$result=Db::getInstance()->Execute($sql)){
                return false;
            }
		if (!Configuration::get('CRIPTOPAY_ORDER_ENVIADA'))
		{
			$order_state = new OrderState();
			$order_state->name = array();

			foreach (Language::getLanguages() as $language)
			{
				if (Tools::strtolower($language['iso_code']) == 'es')
					$order_state->name[$language['id_lang']] = 'Pago enviado a Cripto-Pay.com';
				else
					$order_state->name[$language['id_lang']] = 'Payment sent to Cripto-Pay.com';
			}

			$order_state->send_email = false;
			$order_state->color = '#FFCC99';
			$order_state->hidden = false;
			$order_state->delivery = false;
			$order_state->logable = false;
			$order_state->invoice = false;
                        $order_state->unremovable = false;
                        $order_state->paid = false;
                        

			if ($order_state->add())
			{
				$source = __DIR__.'/views/img/os.gif';
				$destination = __DIR__.'/../img/os/'.(int)$order_state->id.'.gif';
				copy($source, $destination);
			}
			Configuration::updateValue('CRIPTOPAY_ORDER_ENVIADA', (int)$order_state->id);
		}
		return true;
	}

	public function uninstall()
	{
	   // Valores a quitar si desinstalamos el módulo
		if (!Configuration::deleteByName('CRIPTOPAY_TESTNET')
			OR !Configuration::deleteByName('CRIPTOPAY_SANDBOX')
			OR !Configuration::deleteByName('CRIPTOPAY_NOMBRE')
			OR !Configuration::deleteByName('CRIPTOPAY_USUARIO_ID')
			OR !Configuration::deleteByName('CRIPTOPAY_USUARIO_PASSWORD')
                        OR !Configuration::deleteByName('CRIPTOPAY_ORDER_ENVIADA')
			OR !parent::uninstall())
			return false;
		return true;
	}

	private function _postValidation(){
	    // Si al enviar los datos del formulario de configuraciï¿½n hay campos vacios, mostrar errores.
		if (isset($_POST['btnSubmit'])){
			if (empty($_POST['usuario_id']))
				$this->_postErrors[] = $this->l('Necesitamos saber el ID de usuario de Cripto-Pay.com');
			if (empty($_POST['usuario_password']))
				$this->_postErrors[] = $this->l('El passowrd asignado es imprescindible');
		}
	}

	private function _postProcess(){
	    // Actualizar la configuraciÃ³n en la BBDD
			if (isset($_POST['btnSubmit'])){
			Configuration::updateValue('CRIPTOPAY_SANDBOX', $_POST['sandbox']);
			Configuration::updateValue('CRIPTOPAY_TESTNET', $_POST['testnet']);
			Configuration::updateValue('CRIPTOPAY_USUARIO_ID', $_POST['usuario_id']);
			Configuration::updateValue('CRIPTOPAY_USUARIO_PASSWORD', $_POST['usuario_password']);
                        move_uploaded_file($_FILES['usuario_cert_publi']['tmp_name'], __DIR__."/certificados/".basename($_FILES['usuario_cert_publi']['name']));
                        move_uploaded_file($_FILES['usuario_cert_priv']['tmp_name'], __DIR__."/certificados/".basename($_FILES['usuario_cert_priv']['name']));
		}

		$this->_html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="'.$this->l('ok').'" /> '.$this->l('Configuración actualizada').'</div>';
	}

	private function _displaycriptopay()
	{
	    // Aparición el la lista de módulos
		$this->_html .= '<img src="../modules/criptopay/views/img/Cripto-Pay_m.png" style="float:left; margin-right:15px; width:300px"><b>'.$this->l('Este módulo te permite aceptar pagos con Bitcoin & Altcoins.').'</b><br /><br />
		'.$this->l('Si el cliente elije este modo de pago, podrá pagar de forma automática.').'<br /><br /><br />';
	}

	private function _displayForm(){
		$sandbox = Tools::getValue('sandbox', $this->sandbox);
	  	$sandbox_si =  ($sandbox==1) ? ' checked="checked" ' : '';
	  	$sandbox_no =  ($sandbox==0) ? ' checked="checked" '  : '';

                $testnet = Tools::getValue('testnet', $this->testnet);
		$testnet_si =  ($testnet==1) ? ' checked="checked" '  : '';
		$testnet_no =  ($testnet==0) ? ' checked="checked" '  : '';

		// Mostar formulario
		$this->_html .=
		'<form action="'.$_SERVER['REQUEST_URI'].'" method="post" enctype="multipart/form-data">
			<fieldset>
			<legend><img src="../img/admin/contact.gif" />'.$this->l('Configuración de CriptoPay').'</legend>
				<table border="0" width="680" cellpadding="0" cellspacing="0" id="form">
					<tr><td colspan="2">'.$this->l('Por favor completa los datos de config. del comercio').'.<br /><br /></td></tr>
                <tr><td width="215" style="height: 35px;">'.$this->l('ID usuario').'</td><td><input type="text" name="usuario_id" value="'.Tools::getValue('usuario_id', $this->usuario_id).'" style="width: 200px;" /></td></tr>
		<tr><td width="215" style="height: 35px;">'.$this->l('Password Usuario').'</td><td><input type="text" name="usuario_password" value="'.Tools::getValue('usuario_password', $this->usuario_password).'" style="width: 200px;" /></td></tr>
                <tr><td width="215" style="height: 35px;">'.$this->l('Certificado público (.crt)').'</td><td><input type="file" name="usuario_cert_publi"/></td></tr>
                    <tr><td width="215" style="height: 35px;">'.$this->l('Certificado privado (.key)').'</td><td><input type="file" name="usuario_cert_priv"/></td></tr>
		<tr>
		<td width="340" style="height: 35px;">'.$this->l('Usar red testnet').'</td>
			<td>
			<input type="radio" name="testnet" id="testnet" value="1" '.$testnet_si.'/>
			<img src="../img/admin/enabled.gif" alt="'.$this->l('Activado').'" title="'.$this->l('Activado').'" />
			<input type="radio" name="testnet" id="testnet" value="0" '.$testnet_no.'/>
			<img src="../img/admin/disabled.gif" alt="'.$this->l('Desactivado').'" title="'.$this->l('Desactivado').'" />
			</td>
		</tr>
		<tr>
		<td width="340" style="height: 35px;">'.$this->l('Usar entorno de pruebas sandbox').'</td>
			<td>
			<input type="radio" name="sandbox" id="sandbox" value="1" '.$sandbox_si.'/>
			<img src="../img/admin/enabled.gif" alt="'.$this->l('Activado').'" title="'.$this->l('Activado').'" />
			<input type="radio" name="sandbox" id="sandbox" value="0" '.$sandbox_no.'/>
			<img src="../img/admin/disabled.gif" alt="'.$this->l('Desactivado').'" title="'.$this->l('Desactivado').'" />
			</td>
		</tr>
		</table>
			</fieldset>
			<br>
		<input class="button" name="btnSubmit" value="'.$this->l('Guardar configuración').'" type="submit" />
		</form>';
	}

	public function getContent()
	{
	    // Recoger datos
		$this->_html = '<h2>'.$this->displayName.'</h2>';
		if (!empty($_POST))
		{
			$this->_postValidation();
			if (!sizeof($this->_postErrors))
				$this->_postProcess();
			else
				foreach ($this->_postErrors AS $err)
					$this->_html .= '<div class="alert error">'. $err .'</div>';
		}
		else
			$this->_html .= '<br />';
		$this->_displaycriptopay();
		$this->_displayForm();
		return $this->_html;
	}

        
        public function hookDisplayHeader()
        {
          $this->context->controller->addCSS($this->_path.'views/css/criptopay.css', 'all');
        }        
        
	public function hookPayment($params){
            if (!$this->active)
                return;

            $this->smarty->assign(array(
                'urlgenerar' => 'http://'.$_SERVER['HTTP_HOST'].__PS_BASE_URI__.'modules/criptopay/controllers/front/generarpago.php',
                'idpedido' => $params['cart']->id,
                'this_path' => 'http://'.$_SERVER['HTTP_HOST'].__PS_BASE_URI__.'modules/criptopay/'

            ));
            return $this->display(__FILE__, 'payment.tpl');
    }
    
    public function hookPaymentReturn($params)
	{
		if (!$this->active)
			return ;
		global $smarty;
		return $this->display(__FILE__, 'views/templates/pago_correcto.tpl');
	}
}
?>
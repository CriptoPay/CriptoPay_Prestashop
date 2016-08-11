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

class CriptoPayApiRest
{
    
    private $ApiId;
    private $ApiPassword;
    private $ApiCertificados;
    private $ApiNonce;
    private $ApiServidor;
    private static $SESSION = null;
    private static $KeyPublica = null;
    private static $KeyPrivada = null;
    private static $NONCE = 0;
    
    public static $VERSION = "V320";
    
    protected $Parametros = array();
    
    protected $idapi;
    protected $RESPUESTA = array();
    
    /**
     * Constructor para el funcioanmiento con la API REST de CriptoPay
     * @param String $CP_ApiId Identificador de las credenciales
     * @param String $CP_ApiPassword Password privada para la API
     * @param String $CP_ApiCertificados Ruta para buscar los certificados
     * @param Strict $CP_ApiServidor Servidor sobre el que lanzar las peticiones
     */
    public function __construct($CP_ApiId, $CP_ApiPassword, $CP_ApiCertificados, $CP_ApiServidor = null)
    {
        $this->ApiId = $CP_ApiId;
        $this->ApiPassword = $CP_ApiPassword;
        $this->ApiCertificados = $CP_ApiCertificados;
        if (defined('CP_DEBUG') && CP_DEBUG) {
            //En modo debug el servidor siempre será SANDBOX
            $this->ApiServidor = "https://testnet.cripto-pay.com";
        } elseif (!is_null($CP_ApiServidor)) {
            $this->ApiServidor = $CP_ApiServidor;
        } else {
            $this->ApiServidor = "https://api.cripto-pay.com";
        }
                
        //Limpiamos el última slash para prevenir errores
        if (Tools::substr($this->ApiServidor, -1)=="/") {
            $this->ApiServidor = Tools::substr($this->ApiServidor, 0, Tools::strlen($this->ApiServidor)-2);
        } else {
            $this->ApiServidor = $this->ApiServidor;
        }
    }
    
    /**
     * Adición de parametros a enviar
     * @param Array|String $clave Clave del array o array completo a agregar directamente
     * @param String $parametro Elementos a agregar. No se tienen en cuenta si se pasa un Array en $Clave
     */
    public function Set($clave, $parametro = null)
    {
        CriptoPayLog::Debug("Set Parametros");
        if (!is_array($clave)) {
            $this->Parametros[$clave] = $parametro;
        } else {
            $this->Parametros = array_merge($this->Parametros, $clave);
        }
    }
    
    /**
     * Envío de peticiones al servidor de la API
     * @param String $ambito Ámbito sobre el que actuar
     * @param String $funcion Función a ejecutar
     */
    public function Get($ambito, $funcion)
    {
        CriptoPayLog::Debug("Get ".$ambito." ".$funcion);
        //Si no se ha inicializado la sessión la arranca
        $this->ObtenerSesion();
        
        $respuesta = $this->Enviar($ambito, $funcion);
        return $respuesta->respuesta;
    }
    
    /**
     * Inicialización de la sessión para el envío de las peticiones
     */
    private function ObtenerSesion()
    {
        if (is_null(self::$SESSION)) {
            CriptoPayLog::Debug("Obtener Session");
            $this->Enviar("session", "code");
        }
    }
    
    /**
     * Función que realiza los envíos y recibe datos con CURL al servidor
     * @param String $ambito Ámbito sobre el que actuar
     * @param String $funcion Función a ejecutar
     * @return boolean
     * @throws Exception
     * @throws CriptoPayExcepcion
     */
    protected function Enviar($ambito, $funcion)
    {
        $ch = curl_init($this->ApiServidor."/".self::$VERSION."/".$ambito."/".$funcion);
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        //curl_setopt($ch, CURLOPT_CAINFO, "cert.crt");
        
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 25);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25); //timeout in seconds
        
        $peticion =array();
        if (is_null(self::$SESSION)) {
            //Si no hay sessión y se está inicializando envía únicamente ID y Hash del Password
            $peticion['ApiId']=$this->ApiId;
            $peticion['ApiPassword']=  hash_hmac('SHA512', $this->ApiPassword, $this->ApiId);
        } else {
            //Si es petición con sessión abierta se le pasa directamente la Session
            $peticion['session']=self::$SESSION;
            $peticion['datos']=$this->Encriptar(); //Los datos se envían Cifrados internamente
            self::$NONCE = time();
            $peticion['nonce']= self::$NONCE;
        }
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $peticion);
        
        $respuesta_server = curl_exec($ch);
        
        if (curl_errno($ch)) {
            echo curl_error($ch);
        }
        $estado_HTTP = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //Si la cabecera es desconocida devuelve una excepción
        if ($estado_HTTP != 200 && $estado_HTTP != 500 && $estado_HTTP <=900) {
            CriptoPayLog::Debug("PETICION ".$this->ApiServidor."/".self::$VERSION."/".$ambito."/".$funcion);
            //var_dump($peticion);
            //var_dump($respuesta_server);
            throw new CriptoPayExcepcion(sprintf('Curl http error code "%s"', curl_getinfo($ch, CURLINFO_HTTP_CODE)));
        } elseif ($estado_HTTP >=900) {
            //Si la respuesta devuelve cabecera de error personalizado lo procesa
            $this->Error($estado_HTTP);
        }
        
        
        if (is_null(self::$SESSION)) {
            //Si se está inicializando la sessión verifica y guarda el Token recibido
            $respuesta = Tools::jsonDecode($respuesta_server);
            if (json_last_error()===0) {
                if ($respuesta->estado == "success") {
                    self::$SESSION = $respuesta->message;
                    return true;
                } else {
                    throw new CriptoPayExcepcion($respuesta->message);
                }
            } elseif (Tools::strlen($respuesta_server)==24) {
                self::$SESSION = $respuesta_server;
                return true;
            } else {
                //var_dump($respuesta_server);
                throw new CriptoPayExcepcion("Error al fijar la session");
            }
        } elseif (Tools::strlen($respuesta_server)==0) {
            //Si el servidor devuelve cabecera correcta pero ningun dato salta escepción.
            throw new CriptoPayExcepcion("El servidor no ha devuelto ningún dato");
        } else {
            //Si la petición es estandar descifra los datos recibidos y verifica que el nonce es el esperado
            $claro = $this->Desencriptar($respuesta_server);
            $claro = (object)Tools::jsonDecode($claro);
            if ($claro->nonce != self::$NONCE) {
                throw new CriptoPayExcepcion("Hay suplantación de identidad");
            }
            self::$NONCE++;
        }
        curl_close($ch);
        return $claro;
    }
    
    /**
     * Procesamiento de lso posibles errores enviados por HTTP para ahorro de recursos.
     * Les lanza siempre con excepción.
     * @param HTTP_CODE $codigo
     * @throws CriptoPayExcepcion
     */
    private function Error($codigo)
    {
        switch ($codigo) {
            //901-919 Errores en las llamadas o parámteros
            case 901:
                $mensaje = "Ambito/Función no existen";
                break;
            case 902:
                //El usuario actual no puede ejecutar la solicitud enviada
                $mensaje = "No tienes privilegios para acceder a esta función";
                break;
            case 903:
                $mensaje = "Falta algun parametro obligatorio";
                break;
            case 904:
                $mensaje="Falta algún dato en la petición";
                break; //SESSION / AMBITO / FUNCION son siempre obligatorios
            
            //920-939 Errores en el ámbito PAGO
            case 921:
                $mensaje = "El cliente para el pago lleva algún error";
                break; //El cliente/usuario tienen alguna restricción para generar pagos. Revisa tu cuenta de CriptoPay
            
            //940-959 Errores en el ámbito WALLET
            
            //960-979 Errores en el ámbito EXCHANGE
            default:
                $mensaje = "Código desconocido"; //El error no ha sido marcado o está en desarrollo.
                break;
        }
        throw new CriptoPayExcepcion($mensaje, $codigo);
    }


    
    
    /**
     * Carga las claves Públicas y Privadas para las funciones de Cifrado/Descifrado
     * @throws CriptoPayExcepcion
     */
    private function CargarKeys()
    {
        //Busca los certificados en la ruta enviada
        if (!file_exists($this->ApiCertificados."CriptoPay_ApiCert_".$this->ApiId.".crt")) {
            CriptoPayLog::Debug("No se encuentra ".$this->ApiCertificados."CriptoPay_ApiCert_".$this->ApiId.".crt");
            throw new CriptoPayExcepcion("Falta el certificado público");
        }
        if (!file_exists($this->ApiCertificados."CriptoPay_ApiKey_".$this->ApiId.".key")) {
             CriptoPayLog::Debug("No se encuentra ".$this->ApiCertificados."CriptoPay_ApiCert_".$this->ApiId.".crt");
            throw new CriptoPayExcepcion("Falta el certificado privado");
        }
        
        $fp=fopen($this->ApiCertificados."CriptoPay_ApiCert_".$this->ApiId.".crt", "r");
        $pub_key=fread($fp, 8192);
        fclose($fp);
        self::$KeyPublica=openssl_get_publickey($pub_key);
        if (!self::$KeyPublica) {
            if (defined('CP_DEBUG') && CP_DEBUG) {
                throw new CriptoPayExcepcion("El certificado cliente es inválido");
            }
            return false;
        }
        
        $fp=fopen($this->ApiCertificados."CriptoPay_ApiKey_".$this->ApiId.".key", "r");
        $priv_key=fread($fp, 8192);
        fclose($fp);
        self::$KeyPrivada = openssl_get_privatekey($priv_key, $this->ApiPassword);
        if (!self::$KeyPrivada) {
            if (defined('CP_DEBUG') && CP_DEBUG) {
                throw new CriptoPayExcepcion("El certificado privado o la clave es inválido");
            }
            return false;
        }
        return true;
    }
    
    public function __destruct()
    {
        //Libera los recursos de las claves.
        if (self::$KeyPublica != null) {
            openssl_free_key(self::$KeyPublica);
        }
    }
    
    /**
     * Función que encripta los parámetros para su posterior envío.
     * 
     * Las claves son de 4096b por lo que el tamaño máximo de datos a enviar  será de 4096/8 - 11 = 501b
     * 
     * @return boolean
     * @throws CriptoPayExcepcion
     */
    protected function Encriptar()
    {
        if (is_null(self::$KeyPublica)) {
            //Si no stán las claves disponibles las cargamos
            $this->CargarKeys();
        }
        //Para encriptar los datos les pasamos a a String JSON
        $claro = Tools::jsonEncode($this->Parametros);
        openssl_public_encrypt($claro, $finaltext, self::$KeyPublica);
        if (!empty($finaltext)) {
            //Si están bien cifrados les codificamos para poder enviarles por HTTP
            return base64_encode($finaltext);
        } else {
            //Si hay algun problema con la clave o con la longitud de los datos a enviar
            if (defined('CP_DEBUG') && CP_DEBUG) {
                throw new CriptoPayExcepcion("No se pueden Encriptar los datos");
            }
            return false;
        }
    }

    public function Desencriptar($Dcifrados)
    {
        if (is_null(self::$KeyPrivada)) {
            //Si no stán las claves disponibles las cargamos
            $this->CargarKeys();
        }
        
        //Si no se envían datos
        if (Tools::strlen($Dcifrados)>0) {
            $Crypted=openssl_private_decrypt(base64_decode($Dcifrados), $Dclaro, self::$KeyPrivada);
            if (!$Crypted) {
                if (defined('CP_DEBUG') && CP_DEBUG) {
                    echo "CLIENTE_DESENCRIPTAR";
                    var_dump($Dcifrados);
                    throw new CriptoPayExcepcion("No se pueden Desencriptar los datos");
                }
                return false;
            } else {
                $Dclaro = ($this->isJson($Dclaro))?Tools::jsonDecode($Dclaro):$Dclaro;
                return $Dclaro;
            }
        } else {
            return $Dcifrados;
        }
    }
    
    /**
     * Función Auxiliar para verificar los String JSON
     * @param String $string Cadena JSON a verificar
     * @return Bool
     */
    public function isJson($string)
    {
        Tools::jsonDecode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
    
    /**
     * Getter Servidor actual
     * @return String Url servidor;
     */
    public function getServidor()
    {
        return $this->ApiServidor;
    }
}

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

class CriptoPayLog
{
    const TABLA = "log";
        
    private static $instancia = null;
    
    private $inicio;
    
    protected $datoslog;
    protected $contadores;
    protected $FICHERO;
        
    protected $NIVELREGISTRO = 5; //LOG_WARNING;
    protected $NIVELMOSTRAR = 6; //LOG_NOTICE;
        
    const SEPARADOR = ",";
    
    protected function __construct($nivelmostrar = null, $nivelregistro = null, $fichero = null)
    {
        $this->inicio = microtime(true);
        $this->NIVELMOSTRAR = (is_null($nivelmostrar))? $this->NIVELMOSTRAR:$nivelmostrar;
        $this->NIVELREGISTRO = (is_null($nivelregistro))? $this->NIVELREGISTRO:$nivelregistro;
        $this->FICHERO = (is_null($fichero))? "logCriptoPayApiRest.csv":$fichero;
    }
    
    protected static function Instancia($nivelmostrar = null, $nivelregistro = null, $fichero = "logCriptoPayApiRest.csv")
    {
        if (is_null(self::$instancia)) {
            self::$instancia = new CriptoPayLog($nivelmostrar, $nivelregistro, $fichero);
        }
        return self::$instancia;
    }
    
    public function Iniciar($nivelmostrar, $nivelregistro, $fichero)
    {
        $LOG = self::Instancia($nivelmostrar, $nivelregistro, $fichero);
        $LOG->Add(LOG_INFO, "Inicio Script");
    }

    public static function C($dato, $n = 1)
    {
        $LOG = self::Instancia();
        $LOG->Contador($dato, $n);
    }
    
    
    public static function Info($mensaje, $tag = '')
    {
        $LOG = self::Instancia();
        $LOG->Add(6/*LOG_INFO*/, $mensaje, $tag);
    }
    
    public static function Debug($mensaje, $tag = '')
    {
        $LOG = self::Instancia();
        $LOG->Add(7 /*LOG_DEBUG*/, $mensaje, $tag);
    }
    
    public static function Warning($mensaje, $tag = '')
    {
        $LOG = self::Instancia();
        $LOG->Add(LOG_WARNING, $mensaje, $tag);
    }
    
    public static function Alert($mensaje, $tag = '')
    {
        $LOG = self::Instancia();
        $LOG->Add(LOG_ALERT, $mensaje, $tag);
    }
    
    public static function Critical($mensaje, $tag = '')
    {
        $LOG = self::Instancia();
        $LOG->Add(LOG_CRIT, $mensaje, $tag);
    }
    
    public static function Kernel($mensaje, $tag = '')
    {
        $LOG = self::Instancia();
        $LOG->Add(LOG_KERN, $mensaje, $tag);
    }
    
    
    protected function Contador($dato, $n)
    {
        if (isset($this->contadores[$dato])) {
            $this->contadores[$dato]= $this->contadores[$dato]+$n;
        } else {
            $this->contadores[$dato]= $n;
        }
    }

    protected function Add($nivel, $mensaje, $tag = "")
    {
        $debugBacktrace = debug_backtrace();
        $mensaje = preg_replace('/\s+/', ' ', trim($mensaje));
        $log=array(
            "time"=>time(),
            "date"=>date("d-m-Y H:i:s"),
            "nivel"=>$nivel,
            "tag"=>$tag,
            "mensaje"=>$mensaje,
            "fichero"=>@$debugBacktrace[1]['file'],
            "linea"=>@$debugBacktrace[1]['line']
        );

        if (defined('CP_DEBUG') && CP_DEBUG && $this->NIVELMOSTRAR>=$nivel) {
            echo '---LOG---'.$mensaje.PHP_EOL;
        }
         
        $this->datoslog[] = $log;
        
        if ($nivel<=$this->NIVELREGISTRO) {
            $this->AddFichero();
        }
    }
    
    protected function AddFichero()
    {
        if (!file_exists($this->FICHERO)) {
                $headers = 'TIME'.self::SEPARADOR.
                        'DATE'.self::SEPARADOR.
                        'NIVEL'.self::SEPARADOR.
                        'TAG'.self::SEPARADOR.
                        'MENSAJE'.self::SEPARADOR.
                        'FICHERO'.self::SEPARADOR.
                        'LINEA'."\n";
        }

        try {
            $fd = fopen($this->FICHERO, "a");
            if (@$headers) {
                    fwrite($fd, $headers);
            }
            fputcsv($fd, $this->datoslog[count($this->datoslog)-1], self::SEPARADOR);
            fclose($fd);
        }  catch (CriptoPayExcepcion $exc) {
            echo 'No se puede escribir en el fichero '.$exc->getMessage();
        }
    }
    
    public function __destruct()
    {
        $time = microtime(true)-$this->inicio;
        $this->Add(LOG_INFO, "Tiempo total: ".$time);
        if (LOG_DEBUG<=$this->NIVELMOSTRAR) {
            echo '-------------RESUMEN---------------------'.PHP_EOL;
            echo 'Nº Eventos: '.count($this->datoslog).PHP_EOL;
            echo 'Tiempo total '.$time.PHP_EOL;
            if (count($this->contadores)>0) {
                echo '------------CONTADORES--------------------'.PHP_EOL;
                foreach ($this->contadores as $contador => $valor) {
                    echo 'Recuento '.$contador.":".$valor.PHP_EOL;
                }
            }
            echo '-----------FIN RESUMEN-------------------'.PHP_EOL;
        }
    }
}

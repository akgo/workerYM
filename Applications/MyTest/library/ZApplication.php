<?php  if ( ! defined('APP_PATH')) exit('No direct script access allowed');
/*
|---------------------------------------------------------------------------
| Debug Modes
|---------------------------------------------------------------------------
|
| These modes are used when write log
|
*/
define('ZL_ERROR',   1);
define('ZL_WARNING', 2);
define('ZL_PARSE',   4);
define('ZL_NOTICE',  8);
define('ZL_DEBUG',  16);
define('ZL_LOG',    32);

/**
|---------------------------------------------------------------
| load方法，加载类 
|---------------------------------------------------------------
*/
function load($filename){
	if ( $filename == 'Config' ) {
		return Yaf_Registry::get('config');
	} elseif ($filename == 'Debug') {
		return Yaf_Registry::get('debug');
    } elseif ($filename == 'Input') {
        return Yaf_Registry::get('input');
    }else if($filename == 'Loader'){
		return Yaf_Registry::get('loader');
	}
	Yaf_Loader::import($filename);
}

// ---------------------------------------------------------------------------------------------------------------------------
/**
 * 异常类
 */
class ZException extends Exception{}

// ---------------------------------------------------------------------------------------------------------------------------
/**
 * Controller - 控制器接口
 *
 * 所有控制器请继承该接口
 *
 * @package     ZL
 * @subpackage  ZL.libraries
 * @author      ZL Dev Team
 * @category    Singleton 
 */
class ZController extends Yaf_Controller_Abstract
{
    protected function success($data = null, $messageId = null)
	{
        return $this->getView()->success($data, $messageId);
    }
	
    protected function error($messageId, $data = null)
	{
        return $this->getView()->error($messageId, $data);
    }
	
	protected function assign($k, $v = null)
	{
		return $this->getView()->assign($k, $v);
	}

    // 删除
    public function __destruct() {
        //echo 'MRoleModel destruct';
        GameModel::freeAffected();
    }
}

// ---------------------------------------------------------------------------------------------------------------------------
//CI database库重用
function show_error($errstr)
{
	load('Debug')->log($errstr, ZL_ERROR);
}

function log_message($level, $info)
{
	load('Debug')->log($info, ZL_NOTICE);
}

class ZDatabase
{
	static public function factory($params)
	{
		static $instance = array();
		
		define('APPPATH', APP_PATH . '/');
		define('BASEPATH', dirname(__FILE__) . '/');
		require_once('database/DB.php');
		
		if(is_string($params)){
			if (!isset($instance[$params])) {
				$instance[$params] = DB($params);
			}
			return $instance[$params];
		}

		return DB($params);
	}
}

// ---------------------------------------------------------------------------------------------------------------------------
/**
 * Model - 模型接口
 *
 * 所有模型请继承该接口
 *
 * @package     ZL
 * @subpackage  ZL.libraries
 * @author      ZL Dev Team
 * @category    Singleton 
 */
//class ZModel extends GameModel
//{
//    protected $_error = '';
//	
//	public function __construct()
//	{
//		$this->init();
//	}
//	
//	static public function getInstance()
//	{
//		static $instance = array();
//		$className = get_called_class();
//		if(!isset($instance[$className])){
//			$instance[$className] = new $className();
//		}
//		return $instance[$className];
//	}
//	
//	/**
//	 * 初始化
//	 */
//	public function init()
//	{
//		//nothing todo
//	}
//}

// ---------------------------------------------------------------------------------------------------------------------------
/**
 * Config Class - for php 5.4
 *
 * @package     ZL
 * @subpackage  ZL.libraries
 * @author      ZL Dev Team
 * @category    Config
 */

class ZConfig{
    /**
     * Config map
     *
     * @access  private
     * @var     array
     */
    private $_config = array();

    /**
     * Array Class
     *
     * @access  private
     * @var     object
     */
    private $_array;

    /**
     * The Map Of Loaded Config
     *
     * @access  private
     * @var     array
     */
    private $_map = array();

    /**
     * Constructor
     *
     * Write debug information
     */
    public function ZConfig() {
        load('Debug')->log('Config Class Initialized.', ZL_DEBUG);
		$this->load('config');
    }

    /**
     * Get config information
     *
     * @access  public
     * @param   string|array  $item   default ''  the name of config
     * @return  mixed
     */
    public function get($item = '', $return = false) {
        if (is_array($item)) {
            foreach ($item as $v)
                if (isset($this->_config[$v])) $r[] = $this->_config[$v];
            return is_array($r) ? $r : $return;
        }

        $item = strtolower($item);

        foreach (array(':', '->') as $split) {
            if (false !== strpos($item, $split)) {
                $r = explode($split, $item);
                $conf = $this->get(array_shift($r));
                foreach ($r as $v) {
                    $conf = $conf[$v];
                }
                return $conf;
            }
        }

        if (is_string($item) && isset($this->_config[$item])) return $this->_config[$item];
        elseif ($item == '') return $this->_config;
        else return $return;
    }

    /**
     * Load config files
     *
     * @access  public
     * @param   string  $file   the name of a config file
     * @return  object
     */
    public function load($file) {
        if (in_array($file, $this->_map)) {
            load('Debug')->log('Config file ('.$file.'.php) already loaded, Second attempt ignored.', ZL_NOTICE);
            return $this;
        }

        $_config = array();

		if (file_exists(APP_PATH.'/config/'.$file.'.php')) {
			require_once(APP_PATH.'/config/'.$file.'.php');
			
		   	if($config) {
            	$this->_config = array_merge($this->_config, $config);
        	}
			
			if (isset($$file) && is_array($$file)){
				$_config[$file] = is_array($_config[$file]) ? array_merge($_config[$file], $$file) : $$file;
			}
			unset($$file);
			load('Debug')->log('Config file [System config -> '.$file.'.php] be loaded.', ZL_DEBUG);
		} else {
			load('Debug')->log('Config file ['.$file.'.php] fail.', ZL_WARNING);
		}

        $this->_map[] = $file;

        if($_config) {
            $this->_config = array_merge($this->_config, $_config);
        }

        return $this;
    }

    public function set($config) {
        if($config) {
            $this->_config = array_merge($this->_config, $config);
        }
    }
}

// ---------------------------------------------------------------------------------------------------------------------------
/**
 * Debug Class - for php 5.4
 *
 * @package     ZL
 * @subpackage  ZL.libraries
 * @author      ZL Dev Team
 * @category    Debug 
 */
class ZDebug{
    /**
     * date format
     *
     * @access  private
     * @var     string
     */
    private $_date_fmt;

    /**
     * message list
     *
     * @access  private
     * @var     array
     */
    private $_msg;

    /**
     * message id
     *
     * @access  private
     * @var     int
     */
    private $_id;

    /**
     * define the date_format
     */
    public function ZDebug(){
        $this->_date_fmt = 'Y-m-d H:i:s';
        $this->levels = array(
			ZL_ERROR	=> 'ERROR  ',
			ZL_WARNING	=> 'WARNING',
			ZL_PARSE	=> 'PARSE  ',
			ZL_NOTICE	=> 'NOTICE ',
			ZL_DEBUG	=> 'DEBUG  ',
			ZL_LOG		=> 'LOG    ',
        );
        $this->_id = 0;
    }

    /**
     * Write debug information
     *
     * @access  public
     * @param   string  $msg    the message of debug information
     * @return  boolean
     */
    public function log($msg, $level = ZL_ERROR){
        return false;


        // remove in gpm

        if(!(RUNLEVELS & $level)) return false;

        $level = isset($this->levels[$level]) ? $this->levels[$level] : $level;

        // 添加调试信息
        $backtrace = debug_backtrace();
        foreach($backtrace as $row) {
            $this->_msg[] = sprintf('%03d', ++$this->_id).' --> '.date($this->_date_fmt). " --> {$level} --> {$row['file']} - {$row['line']} - {$row['function']} - {$row['class']}";
        }
        if(is_array($msg)){
            foreach($msg as $key => $base ) {
                $this->_msg[] = sprintf('%03d', ++$this->_id).' --> '.date($this->_date_fmt). " --> {$level} --> {$base}";
            }
        }else{
            $this->_msg[] = sprintf('%03d', ++$this->_id).' --> '.date($this->_date_fmt). " --> {$level} --> {$msg}";
        }
        $this->_msg[] = sprintf('%03d', ++$this->_id).' --> '.date($this->_date_fmt). " --> {$level} --> -----------------------------------------------------------------------";
        return true;
    }

    /**
     * Ouput Debug Information
     *
     * @access  public
     * @param   string  $msg
     * @return  void
     */
    public function output($msg, $function = 'print_r'){
        if(function_exists($function)) $function($msg);
        else print_r($function);
        die;
    }

    /**
     * Point debug information
     *
     * @access  public
     * @param   string  $msg    the message of debug information
     * @return  boolean
     */
    public function point($msg, $save = false){
        // remove in gpm
        return true;


        if(!is_string($msg)) $msg = var_export($msg, true);
        $message = date($this->_date_fmt) . " --> ----------------------- point ----------------------------\n";
        $message .= date($this->_date_fmt). ' --> '.$msg."\n";
        $message .= date($this->_date_fmt) . " --> ----------------------------------------------------------\n";
        $this->_msg[] = $message;
        return true;
    }

    public function flush(){
        $this->_save(false);
        $this->_msg = array();    
    }

    /**
     * Save log Message to file
     *
     * @access  private
     * @param   string  $path   the path of log file.
     * @param   string  $file   the name of log file.
     * @param   string  $msg    the message of log information.
     * @return  boolean
     */
    private function _save($end = true){
        if(count($this->_msg) == 0) return ;

        $path = APP_PATH.'/logs/core/';

        $file = date('Y-m-d').'.php';
        if ( $end ) {
            $this->log('Debug End...', ZL_DEBUG);
            $msg = "\n================================================================================\n";
        } else {
            $msg = '';
        }
        
        $msg .= implode($this->_msg, "\n");

        if ( $end ) {
            $msg .= "\n================================================================================\n";
        }

        return true;
    }

    /**
     * Destruct
     *
     * Save debug info
     */
    public function __destruct(){
        $this->_save();
    }
}

// ---------------------------------------------------------------------------------------------------------------------------
/**
 * Input Class - for php 5.0
 *
 * @package     ZL
 * @subpackage  ZL.libraries
 * @author      ZL Dev Team
 * @category    Input
 */
class ZInput{
    /**
     * IP Address
     *
     * @access  private
     * @var     string
     */
    private $_ipAddress;

    /**
     * User Agent
     *
     * @access  private
     * @var     string
     */
    private $_userAgent;

    /**
     * Controller
     *
     * Write Debug Information
     */
    public function ZInput() {
        $this->_ipAddress = false;
        $this->_userAgent = false;
        load('Debug')->log('Input Class Initialized.', ZL_DEBUG);
    }

    /**
     * Online IP
     *
     * @access  public
     * @return  string
     */
    public function ip() {
        if ($this->_ipAddress !== false) return $this->_ipAddress;
        if ($this->server('HTTP_X_FORWARDED_FOR') && strcasecmp($this->server('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $onlineip = $this->server('HTTP_X_FORWARDED_FOR');
        } elseif ($this->server('HTTP_CLIENT_IP') && strcasecmp($this->server('HTTP_CLIENT_IP'), 'unknown')) {
            $onlineip = $this->server('HTTP_CLIENT_IP');
        } elseif ($this->server('REMOTE_ADDR') && strcasecmp($this->server('REMOTE_ADDR'), 'unknown')) {
            $onlineip = $this->server('REMOTE_ADDR');
        }

        preg_match("/[\d\.]{7,15}/", $onlineip, $onlineipmatches);
        $this->_ipAddress = $onlineipmatches[0] ? $onlineipmatches[0] : 'unknown';
        return $this->_ipAddress;
    }

    /**
     * User Agent
     *
     * @access  public
     * @return  string
     */
    public function userAgent() {
        if (false !== $this->_userAgent) {
            return $this->_userAgent;
        }

        $this->_userAgent = $this->server('HTTP_USER_AGENT');

        return $this->_userAgent;
    }

    /**
     * Get a Item From Server Array
     *
     * @access  public
     * @param   string          $index
     * @return  string|array
     */
    public function server($index = '', $return = false) {
        $r = $this->_fetchArray($_SERVER, $index) ? $this->_fetchArray($_SERVER, $index) : getenv($index);
        return $r ? $r : $return;
    }

    /**
     * Foreach Item From Array
     *
     * @access  public
     * @param   string  $array
     * @param   string  $index
     * @return  string
     */
    private function _fetchArray($array, $index = '') {
        if ($index == '') return $array;
        if (isset($array[$index])) {
            return $array[$index];
        } else {
            return false;
        }
    }
}

// ---------------------------------------------------------------------------------------------------------------------------
/**
 * ZL_Driver Class - for php 5.0
 *
 * @package     ZL
 * @subpackage  ZL.Database
 * @author      ZL Dev Team
 * @category    Driver
 */
class ZRedis extends Redis{
    protected $host;
    protected $prefix;
    protected $port;
    protected $serialize;
	protected $multi2;
	
	static private $config = null;
    static private $instance = array();

    // 实例化时，直接连接Redis
    public function ZRedis($params = array())
	{
        load('Debug')->log('Redis Class Initialized.', ZL_DEBUG);
        $this->host                 = '192.168.13.65';
        $this->prefix               = '';
        $this->port                 = '6379';
        $this->serialize            = '';
		$this->multi2				= false;
        $this->password             = '';
        $serializeArray       = array(
            'igbinary' => Redis::SERIALIZER_IGBINARY,
            'php' => Redis::SERIALIZER_PHP,
            'none' => Redis::SERIALIZER_NONE,
        );

        if(is_array($params)){
            foreach($params as $key => $v){
                $this->$key = $v;
            }
        }

        parent::__construct();

        if ( $params['pconnect'] ) {
            $this->pconnect($this->host, $this->port, 0, $params['handle']);
        } else {
            $this->connect($this->host, $this->port);
        }

        if($this->password) {
            $this->auth($this->password);
        }

		if($this->prefix){
			$this->setOption(Redis::OPT_PREFIX, "{$this->prefix}::"); 
		}

		if($this->serialize){
			$this->setOption(Redis::OPT_SERIALIZER, $serializeArray[$this->serialize]);
		}
    }

    /**
     * 取key，不带前辍
     */
    public function nKeys($key = '*') {
        $r = $this->keys($key);
        foreach($r as &$val) {
            $val = str_replace("{$this->prefix}::", '', $val);
        }
        return $r;
    }
	
	public function hMGet($key, $members)
	{
		if(empty($members)){
			return array();
		}
		return parent::hMGet($key, $members);
	}
	
	//重写multi
	public function multi()
	{
		if($this->multi2){
			$request = Yaf_Dispatcher::getInstance()->getRequest();
			$now = time();
			$msg = "==========multi2====================\r\n";
			$msg.= "time   : " . $now . "\n";
			$msg.= "request: " . $request->getControllerName();
			$msg.= "." . $request->getActionName() . "\n";
			$msg.= "params : " . Utils::encode($request->getParams()) . "\n";
			file_put_contents('/data/logs/exception.log', $msg, FILE_APPEND);
		}
		$this->multi2 = true;
		return parent::multi();
	}
	
	//重写discard
	public function discard()
	{
		$this->multi2 = false;
		return parent::discard();
	}
	
	//重写exec
	public function exec()
	{
		$this->multi2 = false;
		parent::set('rdtest', 'aaa');
		parent::get('rdtest');
		$ret = parent::exec();
		if(!$ret || $ret[count($ret) - 1] != 'aaa'){
			$now = time();
			$msg = "==========exec fail====================\r\n";
			$msg.= "date   : " . date('Y-m-d H:i:s', $now) . "\n";
			$msg.= "reqlist: " . json_encode(Yaf_Registry::get('reqlist')) . "\n";
			$msg.= "result : " . json_encode($ret) . "\n";
			file_put_contents('/data/logs/exception.log', $msg, FILE_APPEND);
		}
		return $ret;
	}
	
	static public function factory($name)
	{	
		if (!self::$config){
			self::$config = load('Config')->get('redis');
		}

		if (!isset(self::$config[$name])){
			load('Debug')->log('Load Redis Config Section '.$name.' No-exists.', ZL_ERROR);
			return null;
		}

        if (!isset(self::$instance[$name])){
			self::$instance[$name] = new self(self::$config[$name]);
        }
		
		return self::$instance[$name];
	}
	
	static public function release($name)
	{
		unset(self::$instance[$name]);
	}
}

/**
 * Loader Class - for php 5.4
 *
 * @package     ZL
 * @subpackage  ZL.libraries
 * @author      ZL Dev Team
 * @category    Loader 
 */
class ZLoader{
	public function ZLoader(){
		$this->_error = load('Errors');
	}	

    /**
     * Load Redis Class
     *
     * @access  public
     * @param   arary   $param
     * @return  mixed
     */
    public function redis($name) {
        return ZRedis::factory($name);
    }

    /**
     * Load Database Class
     *
     * @access  public
     * @param   arary   $param
     * @param   boolean $return
     * @return  mixed
     */
    public function database($name) {
        return ZDatabase::factory($name);
    }
}

function print_stack_trace()
{
    $array = debug_backtrace();
   unset($array[0]);
   foreach($array as $row)
    {
       echo $row['file'].':'.$row['line'].', called:'.$row['function']."<br />";
    }
}

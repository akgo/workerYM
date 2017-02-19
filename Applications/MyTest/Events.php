<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;



/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
	
	private static $_instance;
	
	public static function getInstance(){
		$class = 'Yaf_Application';
		$arg = APP_PATH . '/config/application.ini';
		if (self::$_instance == null ) {
			self::$_instance = new $class($arg);
			self::$_instance->bootstrap();
		}
		return self::$_instance;
	}
    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     * 
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id) {
        // 向当前client_id发送数据 
        Gateway::sendToClient($client_id, "Hello $client_id\n");
        // 向所有人发送
        Gateway::sendToAll("$client_id login\n");
    }
    
   /**
    * 当客户端发来消息时触发
    * @param int $client_id 连接id
    * @param mixed $message 具体消息
    */
   public static function onMessage($client_id, $message='c:User;m:list') {
   	
   		$yafInstance = self::getInstance();
   		//$message='c:User;m:stat';
   		
   		//$app->bootstrap();
   		$message = str_replace(PHP_EOL, '', $message);
   		$message = str_replace(array("\r\n", "\r", "\n"), "", $message);
   		$test = self::analyticalData($message);
   		
   		var_dump($test);
   		
   		$rdrGame = load("Loader")->redis('rdrGame');
   		$info = $rdrGame->hGetAll('roleinfo:st_b0f089b66380041f');
   		print_r($info);
   		
   		if($test['c'] && $test['m']) {
   			ob_start();
   			$a = new Yaf_Request_Simple();
   			if ( isset($test['data']) ) {
   				$a->setParam($test['data']);
   			}
   			$a->setControllerName($test['c']);
   			$a->setActionName($test['m']);
   			$rp = $yafInstance->getDispatcher()->dispatch($a);
   			$notice = ob_get_contents();
   			ob_end_clean();
   			 
   			// 向所有人发送
   			if($client_id) {
   				Gateway::sendToAll("$client_id said $notice");
   			} else {
   				echo $notice;
   			}
   			
   		}
   		

   }
   
   public static function analyticalData($message) {
   		$data = explode(';', $message);
   		$result = array();
   		foreach($data as $value) {
   			$data1 = explode(':', $value);
   			$result[$data1[0]] = $data1[1];
   		}
   		return $result;
   		
   }
   
   /**
    * 当用户断开连接时触发
    * @param int $client_id 连接id
    */
   public static function onClose($client_id) {
       // 向所有人发送 
       GateWay::sendToAll("$client_id logout");
   }
}


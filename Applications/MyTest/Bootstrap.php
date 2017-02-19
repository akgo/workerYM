<?php
/**
 * This file should be under the APPLICATION_PATH . "/application/"(which was defined in the config passed to Yaf_Application).
 * and named Bootstrap.php,  so the Yaf_Application can find it 
 */
class Bootstrap extends Yaf_Bootstrap_Abstract
{
	/**
	 * 初始化
	 */
	function _init()
	{
		//load core
		Yaf_Loader::import('ZApplication.php');
		
		Yaf_Registry::set('debug', new ZDebug());
		Yaf_Registry::set('config', new ZConfig());
		Yaf_Registry::set('input', new ZInput());
		Yaf_Registry::set('loader', new ZLoader());
		
	}
}



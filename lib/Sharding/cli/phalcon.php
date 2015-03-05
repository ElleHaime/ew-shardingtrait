<?php

use Phalcon\DI\FactoryDefault\CLI as CliDI,
	Phalcon\CLI\Console as ConsoleApp;

define('DS', DIRECTORY_SEPARATOR);
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__FILE__)) . DS . 'Core');
}
if (!defined('CONVERTER_PATH')) {
	define('CONVERTER_PATH', ROOT_PATH . DS . 'Env' . DS . 'Converter');
}
if (!defined('CONFIG_PATH')) {
	//define('CONFIG_PATH', '/var/www/EventWeekly/config');
	define('CONFIG_PATH', '');
}

$di = new CliDI();

if(is_readable(CONFIG_PATH . '/sharding.php')) {
	include CONFIG_PATH . '/sharding.php';
	$config = new \Phalcon\Config($cfg_sharding);
	$di -> set('shardingConfig', $config);
}
if(is_readable(CONFIG_PATH . '/shardingService.php')) {
	include CONFIG_PATH . '/shardingService.php';
	$config = new \Phalcon\Config($cfg_sharding_service);
	$di -> set('shardingServiceConfig', $config);
}


$di -> set('loader', [
			'className' => '\Phalcon\Loader',
			'calls' => [
				['method' => 'registerNamespaces',
				 'arguments' => [
				 	['type' => 'parameter', 
				 	 'value' => [
				 		'Sharding\Core\Env' => ROOT_PATH . DS . 'Env',
				 	 	'Sharding\Core\Env\Converter' => CONVERTER_PATH,
				 		'Sharding\Core' => ROOT_PATH,
				 		'Sharding\Core\Model' => ROOT_PATH . DS . 'Model',
				 		'Sharding\Core\Env\Helper' => ROOT_PATH . DS . 'Env' . DS . 'Helper',
				 		'Sharding\Objects' => dirname(dirname(__FILE__)) . DS . 'Objects'
				 		]
					]
				 ]
				],
				['method' => 'register'],
			],
		   ]);
$di -> get('loader');

$console = new ConsoleApp();
$console -> setDI($di);
$di -> setShared('console', $console);

array_shift($argv);

foreach ($argv as $index => $action) {
	try {
		$console -> handle(['task' => 'Sharding\Core\Env\Converter\Phalcon',
							'action' => $action]);	
	} catch (\Phalcon\Exception $e) {
		echo $e -> getMessage();
	}
}


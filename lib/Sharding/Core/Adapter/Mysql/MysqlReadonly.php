<?php 

namespace Sharding\Core\Adapter\Mysql;

use Sharding\Core\Adapter\AdapterAbstractReadonly,
	Core\Utils as _U;

class MysqlReadonly extends AdapterAbstractReadonly
{
	use \Sharding\Core\Adapter\Mysql\TMysql;
} 
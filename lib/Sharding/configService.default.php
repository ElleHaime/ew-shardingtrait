<?php

$cfg_sharding_service = [
	'mode' => [
		'oddeven' => [],
		'limitbatch' => [
			'schema' => [
				'mysql' => 'create table $tableName 
							(id int unsigned not null auto_increment primary key,
							 criteria_min int unsigned not null default 1,
							 criteria_max int unsigned not null default 1,
							 dbname varchar(50) not null,
							 tblname varchar(50) not null)' 
			]
		],
		'loadbalance' => [
			'schema' => [
				'mysql' => 'create table $tableName 
							(id int unsigned not null auto_increment primary key,
							 criteria int unsigned not null,
							 dbname varchar(50) not null,
							 tblname varchar(50) not null)'
			]
		]
	]
];

return $cfg_sharding_service;
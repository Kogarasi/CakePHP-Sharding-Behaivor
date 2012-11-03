<?php

/* select server environment */
$config[ 'Shard' ][ 'Connect' ] = 'local';

/* sharding table num */
$config[ 'Shard' ][ 'Divide' ] = 100;

/* cakephp choise from "database.php" with name */
$config[ 'Shard' ][ 'Settings' ] =  array(
	'local' => array(
		'master' => array(
			'master' => 'local',
			'slave' => 'local',
		),

		'tran' => array(
			 array(
				'master' => 'local',
				'slave' => 'local',
			),
			array(
				'master' => 'local',
				'slave' => 'local',
			),
		),
		'log' => array(
			'master' => 'local',
			'slave' => 'local',
		),
	),

	'staging' => array(),
	'production' => array(),
);

# CakePHP-Sharding-Behaivor

## What's this?

Achievement transparently access to multiplment/sharding databases and tables.

## How to use

1. Setup

write config file to "Config" dir.

```PHP
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


```

2. Switch Master/Slave databases and Sharding tables.

modify code.

```php
<?php

class SampleModel extends AppModel
{

	public $actsAs = array( 'Shard' );
	public $useTable = 'samples_0'; // dummy
	public $baseTable = 'samples';

	function __construct()
	{
		parent::__construct();
		$this->connectCluster( 'tran' ); // referenced from Config/shard.php
	}

	public function sampleMethod( $key )
	{

		/* 'key' is used for sharding. ( tableSharding and serverSharding ) */
		$this->connectSlave( array(
			'key' => $key,
		));
		
		return $this->find( 'all' );
	}

	/* shard interface */
	public function tableSharding( $params )
	{
		return sprintf( "%s_%d", $this->baseTable, $params[ 'key' ] % $params[ 'divide' ] );
	}

	/* shard interface */
	public function serverSharding( $params )
	{
		return $params[ 'key' ] % $params[ 'server' ];
	}
}

```
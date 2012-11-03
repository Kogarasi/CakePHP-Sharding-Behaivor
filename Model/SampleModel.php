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
		$table_name = $this->connectSlave( array(
			'key' => $key,
		));
		
		/*
			if SQL is INSERT/UPDATE/DELETE or other reason.
			call connectMaster instead of connectSlave
		*/
		
		/*
		$sql = "SELECT * FROM $table_name";
		$this->qury( $sql );
		*/
		
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

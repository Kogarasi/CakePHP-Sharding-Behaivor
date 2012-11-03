<?php

/**
 * シャーディングを行うためのBehaviorクラス
 *
 * !利用方法
 * $actsAs = array( 'Shard' );
 * $this->connectCluster( 'master' or 'tran' ); // みたいな感じで設定に記述してあるのを選ぶ
 * 
 * insert,update,delete時は
 * $this->conenctMaster()
 * select時は
 * $this->connectSlave()
 * 
 * dsn分割処理を使う場合は serverSharding( $server_num ) を実装する
 * table分割処理を使う場合は tableSharding( $params ) を実装する
 */

class ShardBehavior extends ModelBehavior
{

	private $dbShardSettings = null;

	private $connectEnv;		// 接続環境
	private $connectCluster;	// 接続クラスタ
	private $shardDivide;		// テーブル分割数

	private $shardMethod = array(
		'server' => 'serverSharding',
		'table' => 'tableSharding',
	);

	public function setup( Model $model, $settings = array() )
	{

		// テーブル分割数を取得
		$this->shardDivide = Configure::read( 'Shard.Divide' );

		// 接続環境を取得
		$this->connectEnv = Configure::read( 'Shard.Connect' );
		if( (! isset( $this->connectEnv ) ) || is_null( $this->connectEnv ) || $this->connectEnv === "" )
		{
			throw ShardBehaviorException::emptyConnectConfiguration();
		}

		// 環境ごとの設定を取得
		$_settings = Configure::read( 'Shard.Settings' );

		if( isset( $_settings[ $this->connectEnv ] ) && count( $_settings[ $this->connectEnv ] ) > 0 )
		{
			$this->applyConfiguration( $_settings[ $this->connectEnv ] );
		}
		else
		{
			throw ShardBehaviorException::emptyEnvConfiguration( $this->connectEnv ); 
		}

	}

	private function applyConfiguration( $setting )
	{

		$formatted = array();

		foreach( $setting as $cluster_name => $cluster_conf )
		{

			// duplicate
			if( isset( $formatted[ $cluster_name ] ) )
			{
				throw ShardBehaviorException::alreadyExistsConfiguration( $cluster_name );
			}

			// single cluster
			if( isset( $cluster_conf[ 'master' ] ) || isset( $cluster_conf[ 'slave' ] ) )
			{
				$formatted[ $cluster_name ][ 'count' ] = 1;
				$formatted[ $cluster_name ][ 'config' ] = array( $cluster_conf );
			}
			// multi cluster
			else
			{
				$formatted[ $cluster_name ][ 'count' ] = count( $cluster_conf );
				$formatted[ $cluster_name ][ 'config' ] = $cluster_conf;
			}
		}

		$this->dbShardSetting = $formatted;
		$this->connectCluster = key( $setting );

		//print_r( $this->dbShardSetting );
	}

	public function connectCluster( Model $model, $cluster_name )
	{
		if( isset( $this->dbShardSetting[ $cluster_name ] ) )
		{
			$this->connectCluster = $cluster_name;
		}
		else
		{
			throw ShardBehaviorException::notFoundCluster( $cluster_name );
		}
	}

	/**
	 * DBのマスターノードへ接続
	 */
	public function connectMaster( Model $model, $params = array() )
	{
		$this->selectServer( $model, 'master', $params );
		return $this->selectTable( $model, $params );
	}

	/**
	 * DBのスレーブノードへ接続
	 */
	public function connectSlave( Model $model, $params = array() )
	{
		$this->selectServer( $model, 'slave', $params );
		return $this->selectTable( $model, $params );
	}

	/**
	 * シャーディングのサーバーを選択
	 */
	private function selectServer( Model $model, $type, $params )
	{
		$connect_setting = $this->dbShardSetting[ $this->connectCluster ];

		$current_server_no = $connect_setting[ 'count' ];
		$dsn_no = 0;

		if( method_exists( $model, $this->shardMethod[ 'server' ] ) )
		{
			$params[ 'server' ] = $current_server_no;
			$dsn_no = $model->{ $this->shardMethod[ 'server' ] }( $params );
		}

		$model->setDataSource( $connect_setting[ 'config' ][ $dsn_no ][ $type ] );
		
	}

	/**
	 * シャーディングしたテーブルの選択
	 */
	private function selectTable( Model $model, $params )
	{
		$method_name = $this->shardMethod[ 'table' ];

		// モデル側に関数が設定されていれば実行
		if( method_exists( $model, $method_name ) )
		{
			$params[ 'divide' ] = $this->shardDivide;
			$table_name = $model->{ $method_name }( $params );
		}
		else
		{
			$table_name = $model->useTable;
		}

		$model->setSource( $table_name );
		return $table_name;
	}
}


/**
 * Shard Behavior Exception Class
 */
class ShardBehaviorException extends Exception
{

	public static function emptyConnectConfiguration( $code = 0 )
	{
		return new ShardBehaviorException( "you must be selected connection environment", $code );
	}

	public static function emptyEnvConfiguration( $env_name,  $code = 1 )
	{
		return new ShardBehaviorException( "your select evnironment [$env_name] is empty configuration. please set it", $code );
	}

	public static function alreadyExistsConfiguration( $cluster_name, $code = 2 )
	{
		return new ShardBehaviorException( "cluster name [ $cluster_name ] is duplicate on configuration. please fix it", $code );
	}

	public static function notFoundCluster( $cluster_name, $code = 3 )
	{
		return new ShardBehaviorException( "select cluster [ $cluster_name ] is not found from configuration", $code );
	}
}

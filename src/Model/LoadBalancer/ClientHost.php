<?php

namespace MediaWiki\Extension\EventSimulator\Model\LoadBalancer;

use Wikimedia\Rdbms\LoadBalancer;

class ClientHost {
	private $srvCache;
	private $wanCache;
	private $servers;
	private $deps;

	public function __construct(
		$name,
		$sharedCache,
		MDFDeps $deps
	) {
		$this->srvCache = new \HashBagOStuff;
		$this->wanCache = new \WANObjectCache( [ 'cache' => $sharedCache ] );

		$this->servers = [];
		foreach ( $deps->getScenario()->getLoads() as $name => $load ) {
			$this->servers[] = [
				'host' => $name,
				'load' => $load,
				'type' => 'mysql',
				'password' => '',
				'serverName' => $name,
			];
		}
		$this->deps = $deps;
	}

	public function getLoadBalancer() {
		return new LoadBalancer( [
			'servers' => $this->servers,
			'srvCache' => $this->srvCache,
			'wanCache' => $this->wanCache,
			'databaseFactory' => new MockDatabaseFactory( $this->deps )
		] );
	}
}

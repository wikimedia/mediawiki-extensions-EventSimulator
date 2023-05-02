<?php

namespace MediaWiki\Extension\EventSimulator\Model\LoadBalancer;

use Wikimedia\Rdbms\LoadBalancer;
use Wikimedia\Rdbms\LoadMonitor;
use Wikimedia\TestingAccessWrapper;

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
		$lb = new LoadBalancer( [
			'servers' => $this->servers,
			'srvCache' => $this->srvCache,
			'wanCache' => $this->wanCache,
			'databaseFactory' => new MockDatabaseFactory( $this->deps ),
			'loadMonitor' => [ 'class' => \Wikimedia\Rdbms\LoadMonitor::class ]
		] );
		/** @var LoadMonitor $loadMonitor */
		$loadMonitor = TestingAccessWrapper::newFromObject( $lb )->getLoadMonitor();
		$loadMonitor->setMockTime( $this->deps->getEventLoop()->getCurrentTimeRef() );
		return $lb;
	}
}

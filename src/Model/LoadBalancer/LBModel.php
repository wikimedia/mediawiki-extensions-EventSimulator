<?php

namespace MediaWiki\Extension\EventSimulator\Model\LoadBalancer;

use Wikimedia\EventSimulator\Counter;
use Wikimedia\EventSimulator\EventLoop;
use Wikimedia\EventSimulator\MetricColumn;
use Wikimedia\EventSimulator\Model;
use Wikimedia\EventSimulator\RandomDistribution;
use Wikimedia\EventSimulator\SharedVariable;
use Wikimedia\EventSimulator\TimeColumn;

/**
 * A simulation model used to test LoadBalancer
 */
class LBModel extends Model implements MDFDeps {
	/** @var Scenario */
	private $scenario;
	/** @var Counter */
	private $startCounter;
	/** @var SharedVariable[] */
	private $connCountsByServer;
	/** @var ClientHost[] */
	private $clients;
	/** @var \HashBagOStuff */
	private $sharedCache;

	/**
	 * @param array $params Associative array of options:
	 *   - scenario: One of:
	 *       - plain-s1: Use rate and latency data derived from s1 in production
	 */
	public function __construct( $params ) {
		$this->scenario = Scenario::create( $params['scenario'] ?? 'plain-s1' );
		$this->sharedCache = new \HashBagOStuff;
	}

	public function setup() {
		$this->eventLoop->addTask( [ $this, 'makeRequests' ] );
		$this->startCounter = $this->result->getCounter( 'started' );

		foreach ( $this->scenario->getServerNames() as $serverName ) {
			$this->connCountsByServer[$serverName] = $this->result->getSharedVariable( "$serverName conns" );
			$this->connCountsByServer[$serverName]->set( 0, 0 );
		}

		foreach ( $this->scenario->getClientNames() as $clientName ) {
			$this->clients[] = new ClientHost(
				$clientName,
				$this->sharedCache,
				$this
			);
		}
	}

	public function getResultColumns( $result ) {
		$cols = [
			new TimeColumn( $this->runOptions ),
			new MetricColumn( 'Requests', $result,
				$this->startCounter->getName(), 'mean' ),
		];
		foreach ( $this->connCountsByServer as $serverName => $var ) {
			$cols[] = new MetricColumn( "$serverName conns", $result,
				$var->getName(), 'mean' );
		}
		return $cols;
	}

	/**
	 * Fiber function: randomly start a new request in a new fiber
	 */
	public function makeRequests() {
		while ( true ) {
			$delay = RandomDistribution::exponential( $this->scenario->getRequestRate() );
			$this->eventLoop->sleep( $delay );
			$this->startCounter->incr();
			$this->eventLoop->addTask( [ $this, 'handleRequest' ] );
		}
	}

	/**
	 * Fiber function: perform a request
	 */
	public function handleRequest() {
		$client = $this->getRandomClient();
		$usePrimary = RandomDistribution::uniform( 0, 1 ) <= $this->scenario->getPrimaryRatio();
		$db = $client->getLoadBalancer()->getConnectionRef( $usePrimary ? DB_PRIMARY : DB_REPLICA );
		$db->query( 'SELECT do_work()' );
	}

	/**
	 * Find a ClientHost to handle a request.
	 *
	 * @return ClientHost
	 */
	private function getRandomClient(): ClientHost {
		return $this->clients[ mt_rand( 0, count( $this->clients ) - 1 ) ];
	}

	/**
	 * @return Scenario
	 */
	public function getScenario(): Scenario {
		return $this->scenario;
	}

	/**
	 * @return EventLoop
	 */
	public function getEventLoop(): EventLoop {
		return $this->eventLoop;
	}

	/**
	 * @param string $serverName
	 * @return SharedVariable
	 */
	public function getActiveConnsMetric( $serverName ): SharedVariable {
		return $this->connCountsByServer[$serverName];
	}
}

<?php

namespace MediaWiki\Extension\EventSimulator\Model\LoadBalancer;

use stdClass;
use Wikimedia\EventSimulator\EventSimulatorException;
use Wikimedia\EventSimulator\RandomDistribution;
use Wikimedia\Rdbms\Database;
use Wikimedia\Rdbms\QueryStatus;

/**
 * The fake/instrumented Database class
 */
class MockDatabase extends Database {
	private $deps;

	public function __construct(
		$params,
		MDFDeps $deps
	) {
		parent::__construct( $params );
		$this->deps = $deps;
	}

	protected function doSingleStatementQuery( string $sql ): QueryStatus {
		if ( $sql === 'SELECT do_work()' ) {
			$avgLatency = $this->deps->getScenario()->getAvgLatency( $this->getServerName() );
			$latencyFuzz = $avgLatency * 0.1;
			$minLatency = $avgLatency - $latencyFuzz;
			$maxLatency = $avgLatency + $latencyFuzz;
			$latency = RandomDistribution::uniform( $minLatency, $maxLatency );
			$this->deps->getEventLoop()->sleep( $latency );
		}

		return new QueryStatus(
			new \FakeResultWrapper( [] ),
			0,
			'',
			0
		);
	}

	private function incrConnsMetric( $value ) {
		$metric = $this->deps->getActiveConnsMetric( $this->getServerName() );
		$now = $this->deps->getEventLoop()->getCurrentTime();
		$metric->incr( $value, $now );
	}

	protected function open( $server, $user, $password, $db, $schema, $tablePrefix ) {
		$this->conn = new stdClass;
		$this->incrConnsMetric( 1 );
	}

	public function indexInfo( $table, $index, $fname = __METHOD__ ) {
		throw new EventSimulatorException( 'not implemented' );
	}

	public function strencode( $s ) {
		return addslashes( $s );
	}

	protected function closeConnection() {
		if ( $this->conn ) {
			$this->incrConnsMetric( -1 );
			$this->conn = null;
		}
		return true;
	}

	public function tableExists( $table, $fname = __METHOD__ ) {
		return true;
	}

	protected function fetchAffectedRowCount() {
		return 0;
	}

	public function getType() {
		return 'mysql';
	}

	public function insertId() {
		return 1;
	}

	public function lastErrno() {
		return 0;
	}

	public function lastError() {
		return '';
	}

	public function getSoftwareLink() {
		return '';
	}

	public function getServerVersion() {
		return '';
	}

	public function fieldInfo( $table, $field ) {
		throw new EventSimulatorException( 'not implemented' );
	}
}

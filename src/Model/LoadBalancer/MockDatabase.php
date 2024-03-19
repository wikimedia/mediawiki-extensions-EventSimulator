<?php

namespace MediaWiki\Extension\EventSimulator\Model\LoadBalancer;

use stdClass;
use Wikimedia\EventSimulator\EventSimulatorException;
use Wikimedia\EventSimulator\RandomDistribution;
use Wikimedia\Rdbms\Database;
use Wikimedia\Rdbms\DBConnectionError;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\QueryStatus;
use Wikimedia\Rdbms\Replication\ReplicationReporter;

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
		$this->replicationReporter = new ReplicationReporter(
			$params['topologyRole'],
			$this->logger,
			$params['srvCache']
		);
		$this->deps = $deps;
	}

	protected function doSingleStatementQuery( string $sql ): QueryStatus {
		if ( preg_match( '/SELECT.*do_work/', $sql ) ) {
			$avgLatency = $this->deps->getScenario()->getAvgLatency( $this->getServerName() );
			$latencyFuzz = $avgLatency * 0.1;
			$minLatency = $avgLatency - $latencyFuzz;
			$maxLatency = $avgLatency + $latencyFuzz;
			$latency = RandomDistribution::uniform( $minLatency, $maxLatency );
			$this->deps->getWorkCountMetric( $this->getServerName() )->incr();
			$this->deps->getEventLoop()->sleep( $latency );
		}

		return new QueryStatus(
			new FakeResultWrapper( [] ),
			0,
			'',
			0
		);
	}

	private function incrConnMetrics( $type ) {
		$active = $this->deps->getActiveConnsMetric( $this->getServerName() );
		$total = $this->deps->getTotalConnsMetric( $this->getServerName() );
		$failed = $this->deps->getFailedConnsMetric( $this->getServerName() );
		$now = $this->deps->getEventLoop()->getCurrentTime();

		if ( $type === 'connect' ) {
			$active->incr( 1, $now );
			$total->incr();
		} elseif ( $type === 'disconnect' ) {
			$active->incr( -1, $now );
		} elseif ( $type === 'fail' ) {
			$total->incr();
			$failed->incr();
		}
	}

	protected function open( $server, $user, $password, $db, $schema, $tablePrefix ) {
		$eventLoop = $this->deps->getEventLoop();
		$p = $this->deps->getScenario()->getConnectTimeoutProbability(
			$this->getServerName(),
			$eventLoop->getCurrentTime()
		);
		if ( RandomDistribution::uniform( 0, 1 ) >= $p ) {
			$this->incrConnMetrics( 'connect' );
			$this->conn = new stdClass;
		} else {
			$this->incrConnMetrics( 'connect' );
			$eventLoop->sleep( $this->connectTimeout ?: 3 );
			$this->incrConnMetrics( 'disconnect' );
			$this->incrConnMetrics( 'fail' );
			throw new DBConnectionError( $this, 'connection timeout' );
		}
	}

	public function indexInfo( $table, $index, $fname = __METHOD__ ) {
		throw new EventSimulatorException( 'not implemented' );
	}

	public function strencode( $s ) {
		return addslashes( $s );
	}

	protected function closeConnection() {
		if ( $this->conn ) {
			$this->incrConnMetrics( 'disconnect' );
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

	protected function lastInsertId() {
		return 0;
	}
}

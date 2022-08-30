<?php

namespace MediaWiki\Extension\EventSimulator\Model;

use Wikimedia\EventSimulator\Counter;
use Wikimedia\EventSimulator\Gauge;
use Wikimedia\EventSimulator\MetricColumn;
use Wikimedia\EventSimulator\Model;
use Wikimedia\EventSimulator\RandomDistribution;
use Wikimedia\EventSimulator\SharedVariable;
use Wikimedia\EventSimulator\TimeColumn;
use Wikimedia\ScopedCallback;

class DemoModel extends Model {
	/** @var Counter */
	private $startCounter;
	/** @var SharedVariable */
	private $activeCounter;
	/** @var Gauge */
	private $workDelayTimer;

	public function setup() {
		$this->eventLoop->addTask( [ $this, 'makeRequests' ] );
		$this->startCounter = $this->result->getCounter( 'started' );
		$this->activeCounter = $this->result->getSharedVariable( 'active' );
		$this->activeCounter->set( 0, 0 );
		$this->workDelayTimer = $this->result->getGauge( 'workDelay' );
	}

	public function getResultColumns( $result ) {
		return [
			new TimeColumn( $this->runOptions ),
			new MetricColumn( 'Started', $result,
				$this->startCounter->getName(), 'mean' ),
			new MetricColumn( 'Active', $result,
				$this->activeCounter->getName(), 'mean' ),
			new MetricColumn( 'Work delay (s)', $result,
				$this->workDelayTimer->getName(), 'mean-of-means' )
		];
	}

	public function makeRequests() {
		while ( true ) {
			$delay = RandomDistribution::exponential( 10 );
			$this->eventLoop->sleep( $delay );
			$this->startCounter->incr();
			$workDelay = RandomDistribution::normal( 3, 1 );
			if ( $workDelay < 0 ) {
				$workDelay = 0;
			}
			$this->eventLoop->addTask( [ $this, 'handleRequest' ], $workDelay );
		}
	}

	public function handleRequest( $workDelay ) {
		$counterScope = $this->activeCounter->scopedIncr( $this->eventLoop );
		$timingScope = $this->workDelayTimer->scopedTimer( $this->eventLoop );
		$this->eventLoop->sleep( $workDelay );
		ScopedCallback::consume( $counterScope );
		ScopedCallback::consume( $timingScope );
	}
}

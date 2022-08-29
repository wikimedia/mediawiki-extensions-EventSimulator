<?php

namespace Wikimedia\EventSimulator;

use Wikimedia\RunningStat;
use Wikimedia\ScopedCallback;

/**
 * A metric which represents independent observations of a quantity, such as
 * latency. Observations may be added by the model at any time.
 */
class Gauge extends Metric {
	/** @var RunningStat */
	private $stat;

	public function __construct( $name ) {
		parent::__construct( $name );
		$this->stat = new RunningStat;
	}

	/**
	 * Add an observation
	 *
	 * @param float|int $value
	 */
	public function set( $value ) {
		$this->stat->addObservation( $value );
	}

	/**
	 * Create a ScopedCallback, and when the callback is consumed, add an
	 * observation for the model wall clock time consumed since the
	 * creation of the ScopedCallback.
	 *
	 * @param EventLoop $eventLoop
	 * @return ScopedCallback
	 */
	public function scopedTimer( EventLoop $eventLoop ) {
		$startTime = $eventLoop->getCurrentTime();
		return new ScopedCallback( function () use ( $startTime, $eventLoop ) {
			$this->set( $eventLoop->getCurrentTime() - $startTime );
		} );
	}

	/**
	 * Reset the collected data and return the previous data.
	 *
	 * @return RunningStat
	 */
	public function flush() {
		$stat = $this->stat;
		$this->stat = new RunningStat;
		return $stat;
	}

	public function createEnsemble() {
		return new GaugeEnsemble;
	}

	public function reset() {
		$this->flush();
	}
}

<?php

namespace Wikimedia\EventSimulator;

use Wikimedia\ScopedCallback;

/**
 * A metric representing a numeric global variable which can be written and read.
 * This can be used to measure concurrency.
 *
 * The main output is a continuous-time mean of the variable's value.
 */
class SharedVariable extends Metric {
	/** @var float|int|null The current value */
	private $value;

	/** @var float|int|null The time at which $this->value was set */
	private $lastValueTime;

	/** @var float|int The start time for $this->integralValueDt */
	private $integralStartTime = 0;

	/**
	 * @var float|int The definite integral ∫ f(t) dt from integralStartTime to
	 *   the time of the last set() call where f(t) is the variable value at
	 *   the given time. This integral divided by the time interval is the mean.
	 */
	private $integralValueDt = 0;

	/** @var float|int|null The minimum value */
	private $min;

	/** @var float|int|null The maximum value */
	private $max;

	public function createEnsemble() {
		return new SharedVariableEnsemble;
	}

	/**
	 * @return float|int
	 */
	public function get() {
		return $this->value;
	}

	/**
	 * @return float|int
	 */
	public function getMin() {
		return $this->min;
	}

	/**
	 * @return float|int
	 */
	public function getMax() {
		return $this->max;
	}

	/**
	 * Respond to a reporting time step by resetting recorded mean and
	 * returning the previous mean.
	 *
	 * @param float|int $time The current time in seconds
	 * @return float|int
	 */
	public function flushMean( $time ) {
		if ( $this->value !== null ) {
			$deltaTime = $time - $this->integralStartTime;
			if ( $deltaTime == 0 ) {
				return $this->value;
			} else {
				$this->set( $this->value, $time );
				$mean = $this->integralValueDt / $deltaTime;
				$this->integralValueDt = 0;
				$this->integralStartTime = $time;
				$this->set( $this->value, $time );
				return $mean;
			}
		} else {
			return 0;
		}
	}

	/**
	 * @param float|int $value
	 * @param float|int $time The current time in seconds
	 */
	public function set( $value, $time ) {
		if ( $this->value !== null ) {
			$deltaTime = $time - $this->lastValueTime;
			$this->integralValueDt += $this->value * $deltaTime;
		}
		$this->value = $value;
		$this->lastValueTime = $time;
		if ( $this->min === null || $value < $this->min ) {
			$this->min = $value;
		}
		if ( $this->max === null || $value > $this->max ) {
			$this->max = $value;
		}
	}

	/**
	 * Increment the variable
	 *
	 * @param float|int $delta The amount to add
	 * @param float|int $time The current time in seconds
	 */
	public function incr( $delta, $time ) {
		$this->set( $this->value + $delta, $time );
	}

	/**
	 * Increment the variable, and decrement it when the ScopedCallback is consumed.
	 *
	 * @param EventLoop $eventLoop
	 * @param float|int $delta
	 * @return ScopedCallback
	 */
	public function scopedIncr( EventLoop $eventLoop, $delta = 1 ) {
		$this->incr( $delta, $eventLoop->getCurrentTime() );
		return new ScopedCallback( function () use ( $delta, $eventLoop ) {
			$this->incr( -$delta, $eventLoop->getCurrentTime() );
		} );
	}

	public function reset() {
		$this->value = null;
		$this->integralStartTime = 0;
		$this->integralValueDt = 0;
		$this->min = null;
		$this->max = null;
	}
}

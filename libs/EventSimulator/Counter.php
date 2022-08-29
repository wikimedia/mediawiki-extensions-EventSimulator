<?php

namespace Wikimedia\EventSimulator;

use Wikimedia\ScopedCallback;

/**
 * A metric which can be incremented/decremented by the model.
 * It is flushed and reset to zero on each report time interval.
 */
class Counter extends Metric {
	/** @var int|float */
	private $value = 0;

	/**
	 * Increment the counter
	 * @param int|float $value
	 */
	public function incr( $value = 1 ) {
		$this->value += $value;
	}

	/**
	 * Decrement the counter
	 *
	 * @param int|float $value
	 */
	public function decr( $value = 1 ) {
		$this->incr( -$value );
	}

	/**
	 * Increment the counter, and decrement it when the ScopedCallback is consumed.
	 *
	 * @param int|float $value
	 * @return ScopedCallback
	 */
	public function scopedIncr( $value = 1 ) {
		$this->incr( $value );
		return new ScopedCallback( function () use ( $value ) {
			$this->decr( $value );
		} );
	}

	/**
	 * Reset the counter to zero and return the previous value.
	 *
	 * @return int|float
	 */
	public function flush() {
		$value = $this->value;
		$this->value = 0;
		return $value;
	}

	public function createEnsemble() {
		return new CounterEnsemble;
	}

	public function reset() {
		$this->flush();
	}
}

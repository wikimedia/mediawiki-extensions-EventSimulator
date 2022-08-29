<?php

namespace Wikimedia\EventSimulator;

use Fiber;

/**
 * An event in the event queue
 */
class Event {
	/** @var Fiber */
	private $fiber;

	/**
	 * @param Fiber $fiber
	 */
	public function __construct( $fiber ) {
		$this->fiber = $fiber;
	}

	/**
	 * @return Fiber
	 */
	public function getFiber() {
		return $this->fiber;
	}
}

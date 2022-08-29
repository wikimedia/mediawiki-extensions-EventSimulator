<?php

namespace Wikimedia\EventSimulator;

/**
 * Options for a model run that are needed in a few places
 */
class RunOptions {
	/** @var float|int The reporting time step in seconds */
	public $timeStep;

	/** @var float|int The run duration, in seconds */
	public $duration;

	/**
	 * @param float|int $timeStep
	 * @param float|int $duration
	 */
	public function __construct( $timeStep, $duration ) {
		$this->timeStep = $timeStep;
		$this->duration = $duration;
	}
}

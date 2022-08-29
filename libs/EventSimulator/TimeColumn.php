<?php

namespace Wikimedia\EventSimulator;

/**
 * A column in the output which gives the time
 */
class TimeColumn extends Column {
	private $runOptions;

	public function __construct( RunOptions $runOptions ) {
		parent::__construct( 'Time (s)' );
		$this->runOptions = $runOptions;
	}

	public function getRawValue( $timeIndex ) {
		return $this->runOptions->timeStep * $timeIndex;
	}
}

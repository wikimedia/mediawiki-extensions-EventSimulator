<?php

namespace Wikimedia\EventSimulator;

/**
 * Base class for exceptions thrown by the library
 */
class EventSimulatorException extends \Exception {
	public function __construct( $message ) {
		parent::__construct( "EventSimulator: $message" );
	}
}

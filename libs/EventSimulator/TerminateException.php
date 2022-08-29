<?php

namespace Wikimedia\EventSimulator;

/**
 * Internal exception used to terminate fibers at the end of a model run
 */
class TerminateException extends EventSimulatorException {
	public function __construct() {
		parent::__construct( "terminated" );
	}
}

<?php

namespace Wikimedia\EventSimulator;

use Wikimedia\RunningStat;

/**
 * Ensemble data for a counter metric
 */
class CounterEnsemble extends MetricEnsemble {
	private $stat;

	public function __construct() {
		$this->stat = new RunningStat;
	}

	public function recordTimeStep( Metric $metric, $time ) {
		// @phan-suppress-next-line PhanTypeMismatchArgumentSuperType
		$this->doRecordTimeStep( $metric );
	}

	/**
	 * Record a time step with a specialised metric class
	 *
	 * @param Counter $counter
	 */
	private function doRecordTimeStep( Counter $counter ) {
		$this->stat->addObservation( $counter->flush() );
	}

	public function getRelativeError() {
		return RunningStatUtils::getRelativeError( $this->stat );
	}

	public function getAggregate( $type ) {
		return RunningStatUtils::getAggregate( $this->stat, $type );
	}
}

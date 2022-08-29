<?php

namespace Wikimedia\EventSimulator;

use Wikimedia\RunningStat;

class SharedVariableEnsemble extends MetricEnsemble {
	/** @var RunningStat */
	private $stat;

	public function __construct() {
		$this->stat = new RunningStat;
	}

	public function recordTimeStep( Metric $metric, $time ) {
		// @phan-suppress-next-line PhanTypeMismatchArgumentSuperType
		$this->doRecordTimeStep( $metric, $time );
	}

	/**
	 * Record a time step with a specialised metric type
	 *
	 * @param SharedVariable $variable
	 * @param float|int $time
	 */
	private function doRecordTimeStep( SharedVariable $variable, $time ) {
		$this->stat->addObservation( $variable->flushMean( $time ) );

		// Set the min and max to the true values
		$min = $variable->getMin();
		if ( $min !== null && $min < $this->stat->min ) {
			$this->stat->min = $min;
		}

		$max = $variable->getMax();
		if ( $max !== null && $max > $this->stat->max ) {
			$this->stat->max = $max;
		}
	}

	public function getRelativeError() {
		return RunningStatUtils::getRelativeError( $this->stat );
	}

	public function getAggregate( string $type ) {
		return RunningStatUtils::getAggregate( $this->stat, $type );
	}
}

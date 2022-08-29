<?php

namespace Wikimedia\EventSimulator;

/**
 * An object of this class holds data relating to a single metric at a single
 * point in time, but aggregated over multiple runs of the model.
 */
abstract class MetricEnsemble {
	/**
	 * Record data from the Metric
	 *
	 * @param Metric $metric
	 * @param int|float $time The current time in seconds
	 */
	abstract public function recordTimeStep( Metric $metric, $time );

	/**
	 * Get aggregate data about this time step for output.
	 *
	 * @param string $type The available aggregate types depend on the metric class.
	 * @return int|float
	 */
	abstract public function getAggregate( string $type );

	/**
	 * Get the standard error in the mean, as a fraction of the mean itself.
	 * This is used to decide whether convergence has been reached and so
	 * whether to stop the simulation.
	 *
	 * @return mixed
	 */
	abstract public function getRelativeError();
}

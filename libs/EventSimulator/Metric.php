<?php

namespace Wikimedia\EventSimulator;

/**
 * A metric representing output from the model. A Metric object persists
 * throughout a run of the model.
 */
abstract class Metric {
	/** @var string */
	private $name;

	/**
	 * @param string $name
	 */
	public function __construct( $name ) {
		$this->name = $name;
	}

	/**
	 * Get the metric name
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Create an ensemble for this metric which will aggregate results over
	 * multiple model runs.
	 *
	 * @return MetricEnsemble
	 */
	abstract public function createEnsemble();

	/**
	 * Reset the metric data at the start of a run.
	 */
	abstract public function reset();
}

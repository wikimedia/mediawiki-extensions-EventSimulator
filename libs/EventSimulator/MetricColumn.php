<?php

namespace Wikimedia\EventSimulator;

/**
 * A column which represents a metric
 */
class MetricColumn extends Column {
	/** @var SimulationResult */
	private $result;

	/** @var string */
	private $name;

	/** @var string */
	private $aggregate;

	/**
	 * @param string $header
	 * @param SimulationResult $result
	 * @param string $name
	 * @param string $aggregate
	 */
	public function __construct( $header, SimulationResult $result, $name, $aggregate ) {
		parent::__construct( $header );
		$this->result = $result;
		$this->name = $name;
		$this->aggregate = $aggregate;
	}

	public function getRawValue( $timeIndex ) {
		return $this->result->getEnsemble( $this->name, $timeIndex )
			->getAggregate( $this->aggregate );
	}
}

<?php

namespace Wikimedia\EventSimulator;

/**
 * The overall simulation result, for all time and all model runs.
 */
class SimulationResult {
	/** @var EventLoop|null */
	private $eventLoop;
	/** @var float|int */
	private $timeStep;
	/** @var float|int */
	private $duration;
	/** @var Counter[] */
	private $metrics = [];
	/** @var MetricEnsemble[][] */
	private $metricTimeSeries = [];
	/** @var Column[] */
	private $columns;

	/**
	 * @param RunOptions $runOptions
	 */
	public function __construct( RunOptions $runOptions ) {
		$this->timeStep = $runOptions->timeStep;
		$this->duration = $runOptions->duration;
	}

	/**
	 * Notify this object about the start of a run.
	 *
	 * @param EventLoop $eventLoop
	 */
	public function startRun( EventLoop $eventLoop ) {
		$this->eventLoop = $eventLoop;
		foreach ( $this->metrics as $metric ) {
			$metric->reset();
		}
	}

	/**
	 * @param string $name
	 * @param class-string $className
	 * @return Metric
	 * @throws EventSimulatorException
	 */
	private function getMetric( $name, $className ): Metric {
		if ( !isset( $this->metrics[$name] ) ) {
			$metric = $this->metrics[$name] = new $className( $name );
		} else {
			$metric = $this->metrics[$name];
			if ( !( $metric instanceof $className ) ) {
				throw new EventSimulatorException(
					"The specified metric name is already used by a different type of metric" );
			}
		}
		return $metric;
	}

	/**
	 * Get a Counter metric with the given name. If it does not exist, create and register it.
	 *
	 * @param string $name
	 * @return Counter
	 */
	public function getCounter( $name ): Counter {
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return $this->getMetric( $name, Counter::class );
	}

	/**
	 * Get a Gauge metric with the given name. If it does not exist, create and register it.
	 *
	 * @param string $name
	 * @return Gauge
	 */
	public function getGauge( $name ): Gauge {
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return $this->getMetric( $name, Gauge::class );
	}

	/**
	 * Get a SharedVariable metric with the given name. If it does not exist, create and register it.
	 *
	 * @param string $name
	 * @return SharedVariable
	 */
	public function getSharedVariable( $name ): SharedVariable {
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return $this->getMetric( $name, SharedVariable::class );
	}

	/**
	 * Record a time step for all metrics
	 */
	public function recordTimeStep() {
		$time = $this->eventLoop->getCurrentTime();
		$timeIndex = (int)round( $time / $this->timeStep );
		foreach ( $this->metrics as $name => $metric ) {
			if ( !isset( $this->metricTimeSeries[$name][$timeIndex] ) ) {
				$this->metricTimeSeries[$name][$timeIndex] = $metric->createEnsemble();
			}
			$this->metricTimeSeries[$name][$timeIndex]->recordTimeStep( $metric, $time );
		}
	}

	/**
	 * Get the maximum relative error across all metrics, as a convergence measure.
	 *
	 * @return float|int
	 */
	public function getMaxRelativeError() {
		$error = 0;
		foreach ( $this->metricTimeSeries as $timeSeries ) {
			foreach ( $timeSeries as $ensemble ) {
				$error = max( $error, $ensemble->getRelativeError() );
			}
		}
		return $error;
	}

	/**
	 * @return float|int
	 */
	public function getTimeStep() {
		return $this->timeStep;
	}

	/**
	 * @return int
	 */
	public function getMaxTimeIndex() {
		return (int)ceil( $this->duration / $this->timeStep );
	}

	/**
	 * Get the metric ensemble for the given named metric at the given time index.
	 *
	 * @param string $name
	 * @param int $timeIndex
	 * @return MetricEnsemble
	 */
	public function getEnsemble( $name, $timeIndex ): MetricEnsemble {
		return $this->metricTimeSeries[$name][$timeIndex] ?? $this->metrics[$name]->createEnsemble();
	}

	/**
	 * Create columns for the registered metrics. This is done after the first model run.
	 *
	 * @param Model $model
	 */
	public function registerColumns( Model $model ) {
		$this->columns = $model->getResultColumns( $this );
	}

	/**
	 * @return Column[]
	 */
	public function getColumns() {
		return $this->columns;
	}
}

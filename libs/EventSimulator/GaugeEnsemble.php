<?php

namespace Wikimedia\EventSimulator;

use Wikimedia\RunningStat;

class GaugeEnsemble extends MetricEnsemble {
	/** @var RunningStat */
	private $combinedStat;
	/** @var RunningStat */
	private $meanStat;
	/** @var RunningStat */
	private $minStat;
	/** @var RunningStat */
	private $maxStat;
	/** @var RunningStat */
	private $stdDevStat;

	public function __construct() {
		$this->combinedStat = new RunningStat;
		$this->meanStat = new RunningStat;
		$this->minStat = new RunningStat;
		$this->maxStat = new RunningStat;
		$this->stdDevStat = new RunningStat;
	}

	public function recordTimeStep( Metric $metric, $time ) {
		// @phan-suppress-next-line PhanTypeMismatchArgumentSuperType
		$this->doRecordTimeStep( $metric );
	}

	/**
	 * Record a time step with specialised parameter type.
	 *
	 * @param Gauge $gauge
	 */
	private function doRecordTimeStep( Gauge $gauge ) {
		$stat = $gauge->flush();
		$this->combinedStat->merge( $stat );
		$this->meanStat->addObservation( $stat->getMean() );
		$this->minStat->addObservation( $stat->min );
		$this->maxStat->addObservation( $stat->max );
		$this->stdDevStat->addObservation( $stat->getStdDev() );
	}

	public function getRelativeError() {
		return RunningStatUtils::getRelativeError( $this->meanStat );
	}

	/**
	 * Get an aggregate value
	 * @param string $type One of:
	 *   - min: The overall minimum
	 *   - max: The overall maximum
	 *   - mean: The mean of individual observations
	 *   - stddev: The standard deviation of individual observations
	 *   - mean-of-mins: The mean of the minimum values of each run
	 *   - mean-of-maxes: The mean of the maximum values of each run
	 *   - mean-of-means: The mean of the means of each run
	 *   - etc.
	 * @return float|int
	 */
	public function getAggregate( $type ) {
		$ofParts = explode( '-of-', $type, 2 );
		if ( count( $ofParts ) === 2 ) {
			$aggregate = $ofParts[0];
			$statName = $ofParts[1];
		} else {
			$aggregate = $type;
			$statName = 'combined';
		}
		$stat = $this->getStatByName( $statName );
		return RunningStatUtils::getAggregate( $stat, $aggregate );
	}

	/**
	 * @param string $statName
	 * @return RunningStat
	 * @throws EventSimulatorException
	 */
	private function getStatByName( $statName ) {
		switch ( strtolower( $statName ) ) {
			case 'combined':
				return $this->combinedStat;
			case 'mean':
			case 'means':
				return $this->meanStat;
			case 'min':
			case 'mins':
				return $this->minStat;
			case 'max':
			case 'maxes':
				return $this->maxStat;
			case 'stddev':
			case 'stddevs':
				return $this->stdDevStat;
			default:
				throw new EventSimulatorException( "Unknown stat \"$statName\"" );
		}
	}
}

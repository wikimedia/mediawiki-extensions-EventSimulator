<?php

namespace Wikimedia\EventSimulator;

use Wikimedia\RunningStat;

/**
 * Some utility functions for working with RunningStat objects
 */
class RunningStatUtils {
	/**
	 * Get an aggregate value by name
	 *
	 * @param RunningStat $stat
	 * @param string $type
	 * @return float|int
	 * @throws EventSimulatorException
	 */
	public static function getAggregate( RunningStat $stat, $type ) {
		switch ( strtolower( $type ) ) {
			case 'count':
				return $stat->getCount();
			case 'mean':
				return $stat->getMean();
			case 'stddev':
				return $stat->getStdDev();
			case 'variance':
				return $stat->getVariance();
			case 'min':
				return $stat->min;
			case 'max':
				return $stat->max;
			default:
				throw new EventSimulatorException( "unknown aggregate type \"$type\"" );
		}
	}

	/**
	 * Get the relative standard error
	 *
	 * @param RunningStat $stat
	 * @return float|int
	 */
	public static function getRelativeError( RunningStat $stat ) {
		$divisor = $stat->getMean() * sqrt( $stat->getCount() );
		return $divisor ? $stat->getStdDev() / $divisor : 0;
	}
}

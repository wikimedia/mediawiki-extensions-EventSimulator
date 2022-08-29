<?php

namespace Wikimedia\EventSimulator;

/**
 * Utility functions for getting numbers from random distributions
 */
class RandomDistribution {
	private static $normalSpare;

	/**
	 * Get a number uniformly distributed between $min and $max inclusive
	 *
	 * @param float|int $min
	 * @param float|int $max
	 * @return float|int
	 */
	public static function uniform( $min, $max ) {
		return $min + ( mt_rand() / mt_getrandmax() ) * ( $max - $min );
	}

	/**
	 * Get a number uniformly distributed on the open interval between
	 * $min and $max, so that neither $min nor $max will be returned.
	 *
	 * @param float|int $min
	 * @param float|int $max
	 * @return float|int
	 */
	public static function openUniform( $min, $max ) {
		$delta = ( $max - $min ) / 2 ** 31;
		return self::uniform( $min + $delta, $max - $delta );
	}

	/**
	 * Get a number from the exponential distribution with the given rate.
	 * This can be used to get the time to the next event in a Poisson process.
	 *
	 * @param float|int $rate
	 * @return float|int
	 */
	public static function exponential( $rate ) {
		return -log( self::openUniform( 0, 1 ) ) / $rate;
	}

	/**
	 * Get a random number from a normal distribution.
	 *
	 * @param float|int $mean
	 * @param float|int $stdDev
	 * @return float|int
	 */
	public static function normal( $mean, $stdDev ) {
		// Marsaglia polar method
		if ( self::$normalSpare !== null ) {
			$value = $mean + $stdDev * self::$normalSpare;
			self::$normalSpare = null;
			return $value;
		}
		do {
			$u = self::uniform( -1, 1 );
			$v = self::uniform( -1, 1 );
			$s = $u * $u + $v * $v;
		} while ( $s >= 1 || $s <= 0 );
		$r1 = sqrt( -2 * log( $s ) / $s );
		self::$normalSpare = $v * $r1;
		return $mean + $stdDev * $u * $r1;
	}
}

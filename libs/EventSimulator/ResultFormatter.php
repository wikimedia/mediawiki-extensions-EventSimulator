<?php

namespace Wikimedia\EventSimulator;

/**
 * Base class for classes which can format a SimulationResult, producing a string
 */
abstract class ResultFormatter {
	/**
	 * Factory
	 *
	 * @param array $options Associative array of options:
	 *    - type: optional, may be "csv"
	 * @return ResultFormatter
	 * @throws EventSimulatorException
	 */
	public static function newFromSpec( $options ): ResultFormatter {
		$class = self::getClassFromType( $options['type'] ?? 'default' );
		return new $class( $options['columns'] ?? null );
	}

	/**
	 * @param string $name
	 * @return string Class name
	 * @throws EventSimulatorException
	 */
	private static function getClassFromType( $name ) {
		switch ( $name ) {
			case 'default':
			case 'csv':
				return CsvFormatter::class;
			default:
				throw new EventSimulatorException( "invalid formatter type \"$name\"" );
		}
	}

	/**
	 * @param SimulationResult $result
	 * @return string
	 */
	abstract public function format( SimulationResult $result );
}

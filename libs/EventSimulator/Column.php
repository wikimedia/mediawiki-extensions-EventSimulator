<?php

namespace Wikimedia\EventSimulator;

/**
 * The header and data of a column in the CSV result
 */
abstract class Column {
	/** @var string */
	private $header;

	/**
	 * @param string $header
	 */
	public function __construct( $header ) {
		$this->header = $header;
	}

	/**
	 * Get the column header
	 *
	 * @return string
	 */
	public function getHeader() {
		return $this->header;
	}

	/**
	 * Get a data value
	 *
	 * @param int $timeIndex
	 * @return float|int|string
	 */
	abstract public function getRawValue( $timeIndex );
}

<?php

namespace Wikimedia\EventSimulator;

/**
 * CSV result formatter
 */
class CsvFormatter extends ResultFormatter {
	public function format( SimulationResult $result ) {
		$columns = $result->getColumns();
		$header = [];
		foreach ( $columns as $column ) {
			$header[] = $column->getHeader();
		}
		$rows = [ $header ];
		for ( $i = 0; $i <= $result->getMaxTimeIndex(); $i++ ) {
			$row = [];
			foreach ( $columns as $column ) {
				$row[] = $column->getRawValue( $i );
			}
			$rows[] = $row;
		}
		return self::formatArray( $rows );
	}

	/**
	 * Format a 2-d array as a CSV string
	 *
	 * @param array[] $rows
	 * @return string
	 */
	private static function formatArray( $rows ) {
		$s = '';
		foreach ( $rows as $fields ) {
			$firstField = true;
			foreach ( $fields as $field ) {
				if ( $firstField ) {
					$firstField = false;
				} else {
					$s .= ',';
				}
				if ( strcspn( $field, "\",\n" ) !== strlen( $field ) ) {
					$s .= '"' . str_replace( '"', '""', $field ) . '"';
				} else {
					$s .= $field;
				}
			}
			$s .= "\n";
		}
		return $s;
	}
}

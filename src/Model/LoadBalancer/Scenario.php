<?php

namespace MediaWiki\Extension\EventSimulator\Model\LoadBalancer;

use Wikimedia\EventSimulator\EventSimulatorException;

abstract class Scenario {
	private $serverData;
	private $requestRate;

	/**
	 * Factory
	 *
	 * @param string $name
	 * @return Scenario
	 */
	public static function create( $name ) {
		switch ( $name ) {
			case 'plain-s1':
				return new PlainS1Scenario();
			case 'connect-timeout':
				return new ConnectTimeoutScenario();
			case 'quiet':
				return new QuietScenario();
			case 'slow':
				return new SlowScenario();
			default:
				throw new EventSimulatorException( "unknown scenario \"$name\"" );
		}
	}

	public function __construct() {
		$totalConnRate = 0;
		$connData = [];
		foreach ( explode( "\n", $this->getConnData() ) as $line ) {
			if ( $line === '' ) {
				continue;
			}
			$parts = explode( "\t", $line );
			$connData[$parts[0]] = [
				'avgConns' => (float)$parts[1],
				'connRate' => (float)$parts[2],
				'latency' => (float)$parts[1] / (float)$parts[2]
			];
		}

		$serverData = [];
		foreach ( $this->getLoads() as $server => $load ) {
			$serverData[$server] = $connData[$server];
			$serverData[$server]['load'] = $load;
		}
		$this->adjustServerData( $serverData );
		foreach ( $serverData as $server => $data ) {
			$totalConnRate += $data['connRate'];
		}
		$this->serverData = $serverData;
		$this->requestRate = $totalConnRate;
	}

	/**
	 * Get the global request rate
	 *
	 * @return float|int
	 */
	public function getRequestRate() {
		return $this->requestRate;
	}

	/**
	 * Get the DB server host names
	 *
	 * @return string[]
	 */
	public function getServerNames() {
		return array_keys( $this->serverData );
	}

	/**
	 * Get the MW client host names
	 *
	 * @return string[]
	 */
	public function getClientNames() {
		$names = [];
		for ( $i = 1; $i <= $this->getClientCount(); $i++ ) {
			$names[] = sprintf( "mw1%02d", $i );
		}
		return $names;
	}

	/**
	 * Get the ratio of requests which require master connections
	 *
	 * @return float|int
	 */
	public function getPrimaryRatio() {
		$primaryData = reset( $this->serverData );
		return $primaryData['connRate'] / $this->requestRate;
	}

	/**
	 * Get the average latency for work done on a given server
	 *
	 * @param string $serverName
	 * @param float $time The current simulated time
	 * @return float|int
	 */
	public function getAvgLatency( $serverName, $time ) {
		return $this->serverData[$serverName]['latency'];
	}

	/**
	 * Get the probability of a connection timeout
	 *
	 * @param string $serverName
	 * @param float $time The current simulated time
	 * @return float|int
	 */
	public function getConnectTimeoutProbability( $serverName, $time ) {
		return 0;
	}

	/**
	 * Get a data table giving connection count and connection rate for each DB server.
	 *
	 * @return string
	 */
	abstract protected function getConnData();

	/**
	 * Get the configured loads for all DB servers.
	 *
	 * @return int[]
	 */
	abstract public function getLoads();

	/**
	 * Get the number of client hosts
	 *
	 * @return int
	 */
	abstract protected function getClientCount();

	/**
	 * Adjust the parsed server data
	 * @param array &$serverData
	 */
	protected function adjustServerData( &$serverData ) {
	}
}

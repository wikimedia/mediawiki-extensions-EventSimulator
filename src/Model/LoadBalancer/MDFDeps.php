<?php

namespace MediaWiki\Extension\EventSimulator\Model\LoadBalancer;

use Wikimedia\EventSimulator\Counter;
use Wikimedia\EventSimulator\EventLoop;
use Wikimedia\EventSimulator\SharedVariable;

/**
 * A narrow interface which allows MockDatabase(Factory) to get things from the model
 */
interface MDFDeps {
	/**
	 * @return Scenario
	 */
	public function getScenario(): Scenario;

	/**
	 * @return EventLoop
	 */
	public function getEventLoop(): EventLoop;

	/**
	 * @param string $serverName
	 * @return SharedVariable
	 */
	public function getActiveConnsMetric( $serverName ): SharedVariable;

	/**
	 * @param string $serverName
	 * @return Counter
	 */
	public function getTotalConnsMetric( $serverName ): Counter;

	/**
	 * @param string $serverName
	 * @return Counter
	 */
	public function getFailedConnsMetric( $serverName ): Counter;

	/**
	 * @param string $serverName
	 * @return Counter
	 */
	public function getWorkCountMetric( $serverName ): Counter;
}

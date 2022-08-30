<?php

namespace MediaWiki\Extension\EventSimulator\Model\LoadBalancer;

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
}

<?php

namespace MediaWiki\Extension\EventSimulator\Model\LoadBalancer;

/**
 * A scenario in which the request rate is reduced by a factor of 1000
 */
class QuietScenario extends PlainS1Scenario {
	protected function adjustServerData( &$serverData ) {
		foreach ( $serverData as $server => &$data ) {
			$data['avgConns'] /= 1000;
			$data['connRate'] /= 1000;
		}
	}
}

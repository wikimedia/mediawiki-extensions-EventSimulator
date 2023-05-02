<?php

namespace MediaWiki\Extension\EventSimulator\Model\LoadBalancer;

class ConnectTimeoutScenario extends PlainS1Scenario {
	public function getConnectTimeoutProbability( $serverName, $time ) {
		if ( $serverName === 'db1119' && $time >= 5 ) {
			return 1;
		} else {
			return 0;
		}
	}
}

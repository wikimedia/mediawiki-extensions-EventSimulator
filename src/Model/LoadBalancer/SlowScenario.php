<?php

namespace MediaWiki\Extension\EventSimulator\Model\LoadBalancer;

class SlowScenario extends PlainS1Scenario {
	public function getAvgLatency( $serverName, $time ) {
		$latency = parent::getAvgLatency( $serverName, $time );
		if ( $serverName === 'db1119' && $time > 5 ) {
			$latency *= 10;
		}
		return $latency;
	}
}

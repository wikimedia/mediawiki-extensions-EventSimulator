<?php

namespace MediaWiki\Extension\EventSimulator\Model\LoadBalancer;

use Wikimedia\Rdbms\Database;
use Wikimedia\Rdbms\DatabaseFactory;

/**
 * A DatabaseFactory which creates MockDatabase objects
 */
class MockDatabaseFactory extends DatabaseFactory {
	private $deps;

	public function __construct(
		MDFDeps $deps
	) {
		$this->deps = $deps;
	}

	public function create( $type, $params = [], $connect = Database::NEW_CONNECTED ) {
		return new MockDatabase(
			$params,
			$this->deps
		);
	}
}

<?php

namespace MediaWiki\Extension\EventSimulator\CLI;

use MediaWiki\MediaWikiServices;
use Wikimedia\EventSimulator\ResultFormatter;
use Wikimedia\EventSimulator\SimulationRunner;

$IP = getenv( 'MW_INSTALL_PATH' ) ?: __DIR__ . '/../../../../';
require_once "$IP/maintenance/Maintenance.php";

class RunEventSimulation extends \Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Run an event simulation' );
		$this->addOption( 'class', 'The model class',
			true, true );
		$this->addOption( 'param',
			'A parameter to pass to the class in key=value format. ' .
			'Can be specified multiple times.',
			false, true, 'p', true );
		$this->addOption( 'time-step', 'The output time step, in seconds. Default: 1.',
			false, true );
		$this->addOption( 'duration', 'The simulated duration, in seconds. Default: 1000.',
			false, true );
		$this->addOption( 'iterations', 'The number of iterations',
			false, true );
		$this->addOption( 'convergence',
			'Run until average metrics are accurate to within this relative error. Default 1e-4.',
			false, true );
		$this->addOption( 'output-format',
			'The output format. May be either csv or fixed (default fixed)',
			false, true );
		$this->addOption( 'output-file', 'The output filename (default stdout)',
			false, true, 'o' );
	}

	public function execute() {
		$runnerOptions = [];
		$runnerOptions['model']['class'] = $this->getOption( 'class' );
		$params = [];
		foreach ( $this->getOption( 'param', [] ) as $param ) {
			$parts = explode( '=', $param, 2 );
			if ( count( $parts ) !== 2 ) {
				$this->fatalError( "param must be in the form key=value, found \"$param\"" );
			}
			$params[$parts[0]] = $parts[1];
		}
		$runnerOptions['model']['args'] = [ $params ];
		$runnerOptions['timeStep'] = $this->getOption( 'time-step', 1 );
		$runnerOptions['duration'] = $this->getOption( 'duration', 1000 );
		if ( $this->hasOption( 'iterations' ) ) {
			$runnerOptions['iterations'] = $this->getOption( 'iterations' );
		} else {
			$runnerOptions['convergence'] = $this->getOption( 'convergence', 1e-4 );
		}

		if ( $this->hasOption( 'output-file' ) ) {
			$file = fopen( $this->getOption( 'output-file' ), 'w' );
		} else {
			$file = STDOUT;
		}

		$formatterOptions = [];
		$formatterOptions['type'] = $this->getOption( 'output-format', 'fixed' );
		$formatter = ResultFormatter::newFromSpec( $formatterOptions );

		$runner = SimulationRunner::newFromSpec(
			MediaWikiServices::getInstance()->getObjectFactory(),
			$runnerOptions
		);
		$result = $runner->run();
		fwrite( $file, $formatter->format( $result ) );
	}
}

$maintClass = RunEventSimulation::class;
require_once RUN_MAINTENANCE_IF_MAIN;

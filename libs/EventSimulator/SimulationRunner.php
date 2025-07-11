<?php

namespace Wikimedia\EventSimulator;

use Wikimedia\ObjectFactory\ObjectFactory;

/**
 * The controller for the overall simulation
 */
class SimulationRunner {
	/** @var array */
	private $modelSpec;
	/** @var ObjectFactory */
	private $objectFactory;
	/** @var int|float */
	private $timeStep;
	/** @var int|float */
	private $duration;
	/** @var int|null */
	private $iterations;
	/** @var float|null */
	private $convergence;
	/** @var callable|null */
	private $progressCallback;

	/**
	 * Factory
	 *
	 * @param ObjectFactory $objectFactory
	 * @param array $options
	 *   - model: The model class
	 *   - timeStep: The reporting time step
	 *   - duration: The run duration
	 *   - convergence: The relative error target
	 *   - iterations: The number of runs, used if convergence is absent
	 * @return SimulationRunner
	 */
	public static function newFromSpec( ObjectFactory $objectFactory, $options ) {
		return new self(
			$objectFactory,
			$options['model'],
			$options['timeStep'],
			$options['duration'],
			$options['iterations'] ?? null,
			$options['convergence'] ?? null,
			$options['progressCallback'] ?? null
		);
	}

	/**
	 * @param ObjectFactory $objectFactory
	 * @param array $modelSpec
	 * @param float|int $timeStep
	 * @param float|int $duration
	 * @param int|null $iterations
	 * @param float|null $convergence
	 * @param callable|null $progressCallback
	 */
	public function __construct( $objectFactory, $modelSpec, $timeStep, $duration,
		$iterations, $convergence, $progressCallback
	) {
		$this->objectFactory = $objectFactory;
		$this->modelSpec = $modelSpec;
		$this->timeStep = $timeStep;
		$this->duration = $duration;
		$this->iterations = $iterations;
		$this->convergence = $convergence;
		$this->progressCallback = $progressCallback;
	}

	/**
	 * Run the simulation multiple times until iterations or convergence is reached
	 *
	 * @return SimulationResult
	 * @throws EventSimulatorException
	 */
	public function run() {
		$runOptions = new RunOptions( $this->timeStep, $this->duration );
		$result = new SimulationResult( $runOptions );
		$iterationsDone = 0;
		while ( true ) {
			$model = $this->objectFactory->createObject( $this->modelSpec );
			if ( !( $model instanceof Model ) ) {
				throw new EventSimulatorException( "invalid model" );
			}
			$eventLoop = new EventLoop;
			if ( $this->progressCallback ) {
				$eventLoop->setProgressCallback(
					function ( $time, $fibers ) use ( $iterationsDone ) {
						( $this->progressCallback )( $iterationsDone, $time, $fibers );
					}
				);
			}
			$result->startRun( $eventLoop );
			$model->injectDeps( $eventLoop, $result, $runOptions );
			$model->execute();

			$iterationsDone++;

			if ( $iterationsDone === 1 ) {
				$result->registerColumns( $model );
			}

			if ( $this->convergence !== null ) {
				if ( $iterationsDone > 10 ) {
					$error = $result->getMaxRelativeError();
					if ( $error < $this->convergence ) {
						break;
					}
				}
			} else {
				if ( $iterationsDone >= $this->iterations ) {
					break;
				}
			}
		}
		return $result;
	}
}

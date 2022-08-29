<?php

namespace Wikimedia\EventSimulator;

/**
 * The parent class for models executed by the simulator.
 *
 * A new model object is created for each run.
 */
abstract class Model {
	/** @var EventLoop */
	protected $eventLoop;
	/** @var SimulationResult */
	protected $result;
	/** @var RunOptions */
	protected $runOptions;

	/**
	 * Set dependencies needed by the base class, to avoid the need for
	 * forwarding in the subclass constructors.
	 *
	 * @param EventLoop $eventLoop
	 * @param SimulationResult $result
	 * @param RunOptions $runOptions
	 */
	public function injectDeps( EventLoop $eventLoop, SimulationResult $result, RunOptions $runOptions ) {
		$this->eventLoop = $eventLoop;
		$this->result = $result;
		$this->runOptions = $runOptions;
	}

	/**
	 * This is called before the run begins. The subclass should set up
	 * metrics and schedule events in the EventLoop.
	 */
	abstract public function setup();

	/**
	 * Get the result columns
	 * @param SimulationResult $result
	 * @return Column[]
	 */
	abstract public function getResultColumns( $result );

	/**
	 * Perform a model run
	 */
	public function execute() {
		$this->eventLoop->addTask( [ $this, 'report' ] );
		$this->eventLoop->addTask( [ $this, 'terminate' ] );
		$this->setup();
		$this->eventLoop->run();
	}

	/**
	 * Fiber function: record a time step every interval
	 */
	public function report() {
		while ( true ) {
			$this->result->recordTimeStep();
			$this->eventLoop->sleep( $this->runOptions->timeStep );
		}
	}

	/**
	 * Fiber function: wait until the termination time and then stop the event loop.
	 */
	public function terminate() {
		$this->eventLoop->sleep( $this->runOptions->duration );
		$this->eventLoop->terminate();
	}
}

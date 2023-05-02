<?php

namespace Wikimedia\EventSimulator;

use Fiber;
use SplObjectStorage;

/**
 * The core of the continuous time discrete event simulation. An event loop with
 * an associated event queue. Events are run in fibers. Cooperative multitasking
 * is implemented with a simulated sleep() which suspends the fiber and adds an
 * event which will fire at the specified time.
 *
 * The event loop retrieves the next event from the queue and sets the
 * simulated time to the time specified in the event. It then runs the event
 * handler.
 */
class EventLoop {
	/** @var \SplPriorityQueue */
	private $queue;

	/** @var int|float */
	private $now = 0;

	/** @var SplObjectStorage */
	private $fibers;

	/** @var SplObjectStorage */
	private $startArgs;

	/** @var bool */
	private $isTerminating = false;

	public function __construct() {
		$this->queue = new \SplPriorityQueue();
		$this->queue->setExtractFlags( \SplPriorityQueue::EXTR_BOTH );
		$this->fibers = new SplObjectStorage;
		$this->startArgs = new SplObjectStorage;
	}

	/**
	 * Run the event loop until it is empty or is terminated.
	 */
	public function run() {
		while ( $this->tick() );
	}

	/**
	 * Run a single iteration of the event loop.
	 *
	 * @return bool
	 */
	public function tick() {
		if ( $this->isTerminating ) {
			$this->terminateFibers();
		} else {
			$this->startAndStopFibers();
		}

		do {
			if ( $this->queue->isEmpty() ) {
				return false;
			}
			$res = $this->queue->extract();
			/** @var Event $event */
			[ 'data' => $event, 'priority' => $priority ] = $res;
			$fiber = $event->getFiber();
		} while ( $fiber->isTerminated() );

		$this->now = -$priority;

		try {
			$fiber->resume();
		} catch ( TerminateException $e ) {
		}

		return true;
	}

	/**
	 * Perform a simulated sleep. Suspend the calling fiber until the simulated
	 * delay has elapsed.
	 *
	 * @param float|int $delay The delay in simulated seconds
	 * @throws EventSimulatorException
	 */
	public function sleep( $delay ) {
		$fiber = Fiber::getCurrent();
		if ( !$fiber ) {
			throw new EventSimulatorException( 'sleep() called not from a fiber' );
		}
		if ( !$this->fibers->contains( $fiber ) ) {
			throw new EventSimulatorException( 'sleep() called from an unregistered fiber' );
		}
		if ( $delay < 0 ) {
			$delay = 0;
		}
		$event = new Event( $fiber );
		$this->queue->insert( $event, -( $this->now + $delay ) );
		$fiber->suspend();
	}

	/**
	 * Get the current simulated time in seconds. When the run starts, it is zero.
	 * @return float|int
	 */
	public function getCurrentTime() {
		return $this->now;
	}

	/**
	 * Get a reference to the current time. It should be treated as read-only.
	 * @return float|int
	 */
	public function &getCurrentTimeRef() {
		return $this->now;
	}

	/**
	 * Gracefully terminate the event loop.
	 */
	public function terminate() {
		$this->isTerminating = true;
	}

	/**
	 * Create and register a new fiber. Schedule a callback which will be called
	 * from the new fiber.
	 *
	 * @param callable $callback
	 * @param mixed ...$args
	 */
	public function addTask( callable $callback, ...$args ) {
		if ( $this->isTerminating ) {
			return;
		}
		$fiber = new Fiber( $callback );
		$this->fibers->attach( $fiber );
		$this->startArgs[$fiber] = $args;
	}

	/**
	 * Terminate all suspended fibers by throwing a TerminateException in them.
	 */
	private function terminateFibers() {
		/** @var Fiber $fiber */
		foreach ( $this->fibers as $fiber ) {
			if ( $fiber->isSuspended() ) {
				try {
					$fiber->throw( new TerminateException );
				} catch ( TerminateException $e ) {
				}
			}
		}
	}

	/**
	 * Start fibers which have been created but not started. Clean up fibers
	 * which have terminated.
	 */
	private function startAndStopFibers() {
		/** @var Fiber $fiber */
		foreach ( $this->fibers as $fiber ) {
			if ( !$fiber->isStarted() ) {
				$startArgs = $this->startArgs[$fiber];
				$this->startArgs->detach( $fiber );
				$fiber->start( ...$startArgs );
			}

			if ( $fiber->isTerminated() ) {
				$this->fibers->detach( $fiber );
			}
		}
	}
}

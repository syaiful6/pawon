<?php

namespace Pawon\Queue\Processor;

use Generator;
use SplQueue;

class Scheduler
{
    /**
     *
     */
    protected $maxTaskId = 0;

    /**
     *
     */
    protected $taskMap = []; // taskId => task

    /**
     *
     */
    protected $queue;

    /**
     *
     */
    public function __construct()
    {
        $this->queue = new SplQueue();
    }

    /**
     *
     */
    public function add(Generator $coroutine)
    {
        $tid = ++$this->maxTaskId;
        $task = new Task($tid, $coroutine);
        $this->taskMap[$tid] = $task;
        $this->schedule($task);

        return $tid;
    }

    /**
     *
     */
    public function schedule(Task $task)
    {
        $this->queue->enqueue($task);
    }

    /**
     *
     */
    public function killTask($tid)
    {
        if (!isset($this->taskMap[$tid])) {
            return false;
        }

        unset($this->taskMap[$tid]);

        foreach ($this->queue as $i => $task) {
            if ($task->getTaskId() === $tid) {
                unset($this->queue[$i]);
                break;
            }
        }

        return true;
    }

    /**
     *
     */
    public function run()
    {
        while (!$this->queue->isEmpty()) {
            $task = $this->queue->dequeue();
            $retval = $task->run();

            if ($retval instanceof SystemCall) {
                try {
                    $retval($task, $this);
                } catch (Exception $e) {
                    $task->setException($e);
                    $this->schedule($task);
                }
                continue;
            }

            if ($task->isFinished()) {
                unset($this->taskMap[$task->getTaskId()]);
            } else {
                $this->schedule($task);
            }
        }
    }
}

<?php
/**
* Don't descend from pthreads, normal objects should be used for pools
*/
class Pool {
    protected $size;
    protected $workers;

    /**
    * Construct a worker pool of the given size
    * @param integer $size
    */
    public function __construct($size) {
        $this->size = $size;
        $this->worker = 0;
    }

    /**
    * Start worker threads
    */
    public function start() {
        while (@$this->worker++ < $this->size) {
            $this->workers[$this->worker] = new PooledWorker();
            $this->workers[$this->worker]->start();
        }
        return count($this->workers);
    }

    /**
    * Submit a task to pool
    */
    public function submit(Stackable $task) {
        $this->workers[array_rand($this->workers)]->stack($task);
        return $task;
    }

    /**
    * Shutdown worker threads
    */
    public function shutdown() {
        foreach ($this->workers as $worker)
            $worker->shutdown();
    }
}

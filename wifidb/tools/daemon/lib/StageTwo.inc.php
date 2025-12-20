<?php

class StageTwo extends Stackable {
    /**
    * Construct StageTwo from a part of StageOne data
    * @param int $data
    */
    public function __construct($data) {
        $this->data = $data;
    }

    public function run(){
        printf(
            "Thread %lu had result of: %d\n",
            $this->worker->getThreadId(), $this->data);
    }
}

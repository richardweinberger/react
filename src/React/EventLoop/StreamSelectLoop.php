<?php

namespace React\EventLoop;

class StreamSelectLoop implements LoopInterface
{
    private $timeout;

    private $readStreams = array();
    private $readListeners = array();

    private $writeStreams = array();
    private $writeListeners = array();

    private $stopped = false;

    // timeout = microseconds
    public function __construct($timeout = 1000000)
    {
        $this->timeout = $timeout;
    }

    public function addReadStream($stream, $listener)
    {
        $id = (int) $stream;

        if (!isset($this->readStreams[$id])) {
            $this->readStreams[$id] = $stream;
            $this->readListeners[$id] = $listener;
        }
    }

    public function addWriteStream($stream, $listener)
    {
        $id = (int) $stream;

        if (!isset($this->writeStreams[$id])) {
            $this->writeStreams[$id] = $stream;
            $this->writeListeners[$id] = $listener;
        }
    }

    public function removeReadStream($stream)
    {
        $id = (int) $stream;

        unset(
            $this->readStreams[$id],
            $this->readListeners[$id]
        );
    }

    public function removeWriteStream($stream)
    {
        $id = (int) $stream;

        unset(
            $this->writeStreams[$id],
            $this->writeListeners[$id]
        );
    }

    public function removeStream($stream)
    {
        $this->removeReadStream($stream);
        $this->removeWriteStream($stream);
    }

    public function tick()
    {
        $read = $this->readStreams ?: null;
        $write = $this->writeStreams ?: null;
        $excepts = null;

        if (!$read && !$write) {
            return false;
        }

        if (stream_select($read, $write, $except, 0, $this->timeout) > 0) {
            if ($read) {
                foreach ($read as $stream) {
                    $listener = $this->readListeners[(int) $stream];
                    if (call_user_func($listener, $stream, $this) === false) {
                        $this->removeReadStream($stream);
                    }
                }
            }

            if ($write) {
                foreach ($write as $stream) {
                    if (!isset($this->writeListeners[(int) $stream])) {
                        continue;
                    }

                    $listener = $this->writeListeners[(int) $stream];
                    if (call_user_func($listener, $stream, $this) === false) {
                        $this->removeWriteStream($stream);
                    }
                }
            }
        }

        return true;
    }

    public function run()
    {
        // @codeCoverageIgnoreStart
        $this->stopped = false;

        while ($this->tick() === true && !$this->stopped) {
            // NOOP
        }
        // @codeCoverageIgnoreEnd
    }

    public function stop()
    {
        // @codeCoverageIgnoreStart
        $this->stopped = true;
        // @codeCoverageIgnoreEnd
    }
}

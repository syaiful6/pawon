<?php

namespace Pawon\Core\Http\Server;

use IteratorAggregate;

class FileWrapper implements IteratorAggregate
{
    private $file;

    /**
     *
     */
    public function __construct($file, $blkSize = 8192)
    {
        $this->file = $file;
        $this->blkSize = $blkSize;
    }

    /**
     *
     */
    public function close()
    {
        if (method_exists($this->file, 'close')) {
            $this->file->close();
        }
    }

    /**
     *
     */
    public function getIterator()
    {
        $data = $this->file->read($this->blkSize);
        while ($data) {
            yield $data;
            $data = $this->file->read($this->blkSize);
        }
    }
}

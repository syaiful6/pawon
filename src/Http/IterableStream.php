<?php

namespace Pawon\Http;

use Traversable
use RuntimeException;
use IteratorAggregate;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use function Itertools\iter;

class IterableStream implements StreamInterface, IteratorAggregate
{
    /**
     * @var \Traversable Generally it Generator
     */
    protected $iterable;

    /**
     *
     */
    public function __construct(Traversable $iterable)
    {
        $this->iterable = $iterable;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->getContents();
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->iterable = null;
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        $iterable = $this->iterable;
        $this->iterable = null;
        return $iterable;
    }

    /**
     * Attach a new iterable to the instance.
     *
     * @param Traversable $iterable
     */
    public function attach(Traversable $iterable)
    {
        $this->iterable = $iterable;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function tell()
    {
        throw new RuntimeException('Iterable streams cannot tell position');
    }

    /**
     * dont advance the iterator here, we dont know if this iterator is forward
     * iterator.
     */
    public function eof()
    {
        return empty($this->iterable);
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        throw new RuntimeException('Iterable streams cannot seek position');
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        throw new RuntimeException('Iterable streams cannot rewind position');
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function write($string)
    {
        throw new RuntimeException('Iterable streams cannot write');
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function read($length)
    {
        throw new RuntimeException('Iterable streams cannot read');
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        //pretend we always receive an forward iterator
        $iterator = iter($this->iterable);
        // not so effective for this purpose, it still hold data
        return join('', iterator_to_array($iterator));
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        $metadata = [
            'eof' => $this->eof(),
            'stream_type' => 'iterable',
            'seekable' => false
        ];

        if (null === $key) {
            return $metadata;
        }

        if (! array_key_exists($key, $metadata)) {
            return null;
        }

        return $metadata[$key];
    }

    /**
     * This is the recommended way to emit data from this stream. It allow us to
     * not holding any data that may be large, commonly the iterable passed here
     * is Generator.
     *
     * @return \Generator
     */
    public function getIterator()
    {
        foreach ($this->iterable as $item) {
            yield $item;
        }
    }
}

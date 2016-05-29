<?php

namespace Pawon\Session;

use Headbanger\MutableMapping;
use Headbanger\HashMap;
use Headbanger\ArrayList;
use Pawon\Session\Backends\SessionBackendInterface;
use function Itertools\to_array;

class Store extends MutableMapping
{
    /**
     * @var bool to indicate the session data accessed
     */
    private $accessed = false;

    /**
     * @var bool to indicate the entry has been modified
     */
    private $modified = false;

    /**
     * @var session backend used by the store
     */
    protected $backend;

    /**
     * The loaded session.
     */
    protected $attributes = [];

    /**
     * @var string the session name (used to set it on cookie)
     */
    protected $name;

    /**
     * @var string the session id
     */
    protected $id;

    /**
     * @var flags to indicate the data has been loaded from backend
     */
    protected $isLoaded = false;

    /**
     *
     */
    public function __construct($name, SessionBackendInterface $backend, $id = null)
    {
        $this->name = $name;
        $this->backend = $backend;
        $this->setId($id);
        $this->attributes = new HashMap();
    }

    /**
     * Return true if our entry modified, it mean user set new entry, unsetting
     * the old value or replacing.
     *
     * @return bool
     */
    public function isModified()
    {
        return $this->modified;
    }

    /**
     * Return true if our session data accessed.
     *
     * @return bool
     */
    public function isAccessed()
    {
        return $this->accessed;
    }

    /**
     * Clear / empty the session data without generating new session id.
     */
    public function clear()
    {
        $this->attributes->clear();
    }

    /**
     * Return the count of all entry in this session.
     *
     * @return int
     */
    public function count()
    {
        if (!$this->isLoaded) {
            $this->loadFromBackend();
        }

        return count($this->attributes);
    }

    /**
     * Test if this mapping contains an item (key).
     *
     * @return bool
     */
    public function contains($item)
    {
        if (!$this->isLoaded) {
            $this->loadFromBackend();
        }

        return $this->offsetExists($item);
    }

    /**
     * pop the session entry based the provided key, if the key exists then the
     * entry will removed and return it. If not exists the default parameter used
     * as returned value.
     *
     * @param mixed $key     The key of the entry
     * @param mixed $default The default value in case the key not found in session
     *
     * @return mixed
     */
    public function pop($key, $default = null)
    {
        if (!$this->isLoaded) {
            $this->loadFromBackend();
        }
        $this->modified = $this->modified || $this->contains($key);

        return $this->attributes->pop($key, $default);
    }

    /**
     * prevent the call to our parent, this not supported.
     */
    public function popItem()
    {
        throw new \RuntimeException('not allowed to pop item');
    }

    /**
     * IteratorAggregate implementation.
     *
     * @return \Iterator
     */
    public function getIterator()
    {
        if (!$this->isLoaded) {
            $this->loadFromBackend();
        }

        return $this->attributes->getIterator();
    }

    /**
     *
     */
    public function offsetSet($key, $value)
    {
        if (!$this->isLoaded) {
            $this->loadFromBackend();
        }
        $this->modified = true;
        $this->attributes[$key] = $value;
    }

    /**
     *
     */
    public function offsetUnset($key)
    {
        if (!$this->isLoaded) {
            $this->loadFromBackend();
        }
        $this->modified = true;
        unset($this->attributes[$key]);
    }

    /**
     *
     */
    public function offsetGet($key)
    {
        if (!$this->isLoaded) {
            $this->loadFromBackend();
        }

        return $this->attributes[$key];
    }

    /**
     *
     */
    private function loadFromBackend()
    {
        if ($this->isLoaded) {
            return;
        }
        $this->accessed = true;
        $data = $this->backend->read($this->getId());
        if ($data) {
            $data = @unserialize($this->prepareForUnserialize($data));

            if ($data !== false && $data !== null && is_array($data)) {
                $this->attributes->update(new ArrayList($data));
            }
        }
        $this->isLoaded = true;
    }

    /**
     *
     */
    protected function prepareForUnserialize($data)
    {
        return $data;
    }

    /**
     * Get the session id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the sessiond id, if the id is not valid then generate the valid one.
     *
     * @param mixed $id
     */
    public function setId($id)
    {
        if (!$this->isValidId($id)) {
            $id = $this->generateSessionId();
        }

        $this->id = $id;
    }

    /**
     * Creates a new session Id, while retaining the current session data.
     */
    public function cycleId()
    {
        $data = $this->attributes;
        $id = $this->getId();
        if ($id) {
            $this->flush();
        }
        $this->attributes = $data;
    }

    /**
     * Determine if this is a valid session ID.
     *
     * @param string $id
     *
     * @return bool
     */
    public function isValidId($id)
    {
        return is_string($id) && preg_match('/^[a-f0-9]{40}$/', $id);
    }

    /**
     * Get a new, random session ID.
     *
     * @return string
     */
    protected function generateSessionId()
    {
        $string = '';
        $length = 16;
        while (($len = strlen($string)) < $length) {
            $size = $length - $len;

            $bytes = random_bytes($size);

            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return sha1(uniqid('', true).$string.microtime(true));
    }

    /**
     *
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Removes the current session data from the database and regenerates the
     * session id.
     */
    public function flush()
    {
        $this->clear();
        $this->destroy();
        $this->setId(null);
    }

    /**
     *
     */
    public function save()
    {
        $items = to_array($this->attributes->items());
        $this->backend->write($this->getId(), $this->prepareForStorage(serialize($items)));
    }

    /**
     *
     */
    protected function prepareForStorage($items)
    {
        return $items;
    }

    /**
     *
     */
    public function destroy()
    {
        $this->backend->destroy($this->getId());
    }

    /**
     *  Cleanup old sessions.
     */
    public function gc($lifetime)
    {
        $this->backend->gc($lifetime);
    }
}

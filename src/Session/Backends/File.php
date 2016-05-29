<?php

namespace Pawon\Session\Backends;

use ErrorException;
use Symfony\Component\Finder\Finder;

class File implements SessionBackendInterface
{
    /**
     * path to write the session.
     *
     * @var string
     */
    private $path;

    protected $prefix = 'expressive-session-';

    /**
     *
     */
    public function __construct($path = null)
    {
        $this->path = $path ?: sys_get_temp_dir();
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return true;
    }

    /**
     *
     */
    private function sessionIdToFile($id)
    {
        return $this->path.'/'.$this->prefix.$id;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        if (file_exists($path = $this->sessionIdToFile($sessionId))) {
            return file_get_contents($path);
        }

        return '';
    }

    /**
     *
     */
    public function write($sessionId, $data)
    {
        file_put_contents($this->sessionIdToFile($sessionId), $data, LOCK_EX);
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        try {
            @unlink($this->sessionIdToFile($sessionId));
        } catch (ErrorException $e) {
            // pass it
        }
    }

    /**
     *
     */
    public function gc($lifetime)
    {
        $files = Finder::create()
                    ->in($this->path)
                    ->files()
                    ->name($this->prefix.'*')
                    ->ignoreDotFiles(true)
                    ->date('<= now - '.$lifetime.' seconds');

        foreach ($files as $file) {
            try {
                @unlink($file->getRealPath());
            } catch (ErrorException $e) {
                //pass, maybe we dont have permission, but we dont care
            }
        }
    }
}

<?php

namespace Pawon\Core;

use RuntimeException;
use Illuminate\Support\Str;
use Illuminate\Encryption\Encrypter;
use Illuminate\Encryption\McryptEncrypter;
use Interop\Container\ContainerInterface as Container;

class EncrypterFactory
{
    /**
     *
     */
    public function __invoke(Container $container)
    {
        $config = $container->get('config');

        if (Str::startsWith($key = $config['key'], 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return $this->getEncrypterForKeyAndCipher($key, $config['cipher']);
    }

    /**
     *
     */
    protected function getEncrypterForKeyAndCipher($key, $cipher)
    {
        if (Encrypter::supported($key, $cipher)) {
            return new Encrypter($key, $cipher);
        } elseif (McryptEncrypter::supported($key, $cipher)) {
            return new McryptEncrypter($key, $cipher);
        } else {
            throw new RuntimeException(
                'No supported encrypter found. The cipher and / or key length are invalid.'
            );
        }
    }
}

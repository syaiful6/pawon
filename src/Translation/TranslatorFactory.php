<?php

namespace Pawon\Translation;

use Interop\Container\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class TranslatorFactory
{
    /**
     *
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        if ($requestedName === TranslatorInterface::class) {
            return $this->createTranslator($container);
        } elseif ($requestedName === LoaderInterface::class) {
            return $this->createLoader($container);
        } else {
            throw new \RuntimeException("can\'t create $requestedName");
        }
    }

    /**
     *
     */
    protected function createTranslator($container)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $locale = isset($config['locale']) ? $config['locale'] : 'id';
        $fallback = isset($config['fallback_locale']) ? $config['fallback_locale'] : 'en';

        $translator = new Translator($container->get(LoaderInterface::class), $locale);

        $translator->setFallback($fallback);

        return $translator;
    }

    /**
     *
     */
    protected function createLoader($container)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $path = isset($config['lang_dir']) ? $config['lang_dir'] : 'lang';
        return new FileLoader($path);
    }
}

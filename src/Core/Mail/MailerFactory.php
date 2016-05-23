<?php

namespace Pawon\Core\Mail;

use Swift_Mailer;
use Swift_SmtpTransport as SmtpTransport;
use Swift_MailTransport as MailTransport;
use Swift_SendmailTransport as SendmailTransport;
use Interop\Container\ContainerInterface as Container;
use Zend\Expressive\Template\TemplateRendererInterface as Renderer;

class MailerFactory
{
    /**
     *
     */
    public function __invoke(Container $container)
    {
        if ($container->has('config')) {
            $config = $container->get('config');
            $mailConfig = $config['mail'];

            $transport = $this->createTransport($mailConfig);
            $mailer = new Swift_Mailer($transport);
            $template = $container->get(Renderer::class);

            return new Mailer($template, $mailer);
        }
        throw \RuntimeException('cant create mailer without configuration');
    }

    /**
     *
     */
    protected function createTransport($config)
    {
        switch ($config['driver']) {
            case 'smtp':
                return $this->createSmtpTransport($config);
            case 'mail':
                return $this->createMailTransport($config);
            case 'sendmail':
                return $this->createSendMailTransport($config);
            default:
                throw new \RuntimeException('uknown mail transport');
        }
    }

    /**
     *
     */
    protected function createSmtpTransport($config)
    {
        $transport = SmtpTransport::newInstance(
            $config['host'],
            $config['port']
        );

        if (isset($config['encryption'])) {
            $transport->setEncryption($config['encryption']);
        }

        if (isset($config['username'])) {
            $transport->setUsername($config['username']);

            $transport->setPassword($config['password']);
        }

        return $transport;
    }

    /**
     *
     */
    protected function createSendMailTransport($config)
    {
        $command = $config['command'];

        return SendmailTransport::newInstance($command);
    }

    /**
     *
     */
    protected function createMailTransport()
    {
        return MailTransport::newInstance();
    }
}

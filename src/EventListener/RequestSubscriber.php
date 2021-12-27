<?php

namespace Symprowire\EventListener;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class RequestSubscriber implements EventSubscriberInterface
{

    /**
     * @param RequestEvent $event
     * @return Request
     *
     */
    #[Pure]
    public function onKernelRequest(RequestEvent $event): Request
    {
        return $event->getRequest();
    }

    /**
     * @return string[]
     *
     * Subscribe to the RequestEvent.
     * Will not be triggered if the Request results in a Exception
     */
    #[ArrayShape([RequestEvent::class => "string"])]
    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onKernelRequest'
        ];
    }
}

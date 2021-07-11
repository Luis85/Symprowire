<?php


namespace App\Symprowire;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RequestListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return ['response' => 'onResponse'];
    }

    public function onResponse(ResponseEvent $event)
    {

    }
}

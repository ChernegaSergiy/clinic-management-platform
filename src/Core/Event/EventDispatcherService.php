<?php

namespace App\Core\Event;

use Symfony\Component\EventDispatcher\EventDispatcher;

class EventDispatcherService
{
    private static ?EventDispatcher $dispatcher = null;

    public static function setDispatcher(EventDispatcher $dispatcher): void
    {
        self::$dispatcher = $dispatcher;
    }

    public static function getDispatcher(): EventDispatcher
    {
        if (!self::$dispatcher) {
            throw new \RuntimeException('EventDispatcher not initialized');
        }
        return self::$dispatcher;
    }
}

<?php

namespace Mongolid\Event;

/**
 * Provides the service of event firing.
 *
 * @package Mongolid
 */
class EventTriggerService
{
    /**
     * The one who are going to actually trigger the events to the rest of the application
     * @var EventTriggerInterface
     */
    protected $dispatcher;

    /**
     * Registers a object that will have the responsibility of firing events to
     * the rest of the application.
     *
     * @param  EventTriggerInterface $dispatcher Event trigger object.
     *
     * @return void
     */
    public function registerEventDispatcher(EventTriggerInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Triggers / Dispatches a new event to the registered event handlers if
     * they have been registered.
     *
     * @param  string  $event   Identification of the event.
     * @param  mixed   $payload Data that is going to be sent to the event handler.
     * @param  boolean $halt    The output of the event handler will be used in a conditional inside the context of
     *                          where the event is being fired. This means that, if the event handler returns false,
     *                          it will probably stop the action being executed, for example, "saving".
     *
     * @return mixed            Event handler return. The importance of this return is determined by $halt
     */
    public function fire(string $event, $payload, bool $halt = false)
    {
        if ($this->dispatcher) {
            return $this->dispatcher->fire($event, $payload, $halt);
        }

        return true;
    }
}

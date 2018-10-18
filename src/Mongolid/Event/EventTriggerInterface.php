<?php
namespace Mongolid\Event;

/**
 * An interface that should be implemented and injected in order to have events
 * triggered from Mongolid.
 */
interface EventTriggerInterface
{
    /**
     * Triggers / Dispatches a new event to the event handlers or listeners that
     * are being used.
     *
     * @param string $event   identification of the event
     * @param mixed  $payload data that is going to be sent to the event handler
     * @param bool   $halt    The output of the event handler will be used in a conditional inside the context of
     *                        where the event is being fired. This means that, if the event handler returns false,
     *                        it will probably stop the action being executed, for example, "saving".
     *
     * @return mixed Event handler return. The importance of this return is determined by $halt
     */
    public function fire(string $event, $payload, bool $halt);
}

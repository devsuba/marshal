<?php

declare(strict_types=1);

namespace Marshal\Scheduler;

use Marshal\Scheduler\Event\RunTaskEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

final class TaskRunner
{
    public function __construct(private TransportInterface $transport, private EventDispatcherInterface $eventDispatcher)
    {
    }

    public function run(): void
    {
        $due = $this->transport->getDue();
        foreach ($due as $task) {
            \assert($task instanceof Task);

            $event = new RunTaskEvent($task->getEventName(), $task->getEventParams());
            $this->eventDispatcher->dispatch($event);
        }
    }
}

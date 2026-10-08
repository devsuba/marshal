<?php

declare(strict_types= 1);

namespace Marshal\Scheduler\Transport;

use Marshal\Database\Query\Create;
use Marshal\Database\Query\Select;
use Marshal\Scheduler\TransportInterface;
use Marshal\Utils\Logger\LoggerManager;
use Marshal\Scheduler\Task;

final class DatabaseTransport implements TransportInterface
{
    public function getDue(): array|\Traversable
    {
        return Select::from(Task::class)
            ->fetchAllAssociative();
    }

    public function schedule(Task $task): bool
    {
        // @todo apply checks whether task should be saved

        try {
            Create::target($task);
        } catch (\Throwable $e) {
            LoggerManager::get()->error($e->getMessage());
            return false;
        }

        return true;
    }
}

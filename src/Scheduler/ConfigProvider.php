<?php

namespace Marshal\Scheduler;

use Marshal\Database\Schema\Content;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            "dependencies" => $this->getDependencies(),
            "navigation" => $this->getRoutesConfig(),
            "schema" => $this->getSchemaConfig(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            "aliases" => [
                TransportInterface::class                     => Transport\DatabaseTransport::class,
            ],
            "factories" => [
                TaskRunner::class                             => TaskRunnerFactory::class,
                Transport\DatabaseTransport::class            => \Laminas\ServiceManager\Factory\InvokableFactory::class,
            ],
        ];
    }

    private function getRoutesConfig(): array
    {
        return [
            "paths" => [
                "/tasks" => [
                    "methods" => ["GET"],
                    "middleware" => Handler\TasksDashboardHandler::class,
                    "name" => Handler\TasksDashboardHandler::DASHBOARD_HANDLER,
                ],
            ],
        ];
    }

    private function getSchemaConfig(): array
    {
        return [
            "properties" => [
                Task::EVENT_NAME => [
                    "label" => "Event Name",
                    "description" => "Event name",
                    "name" => "event_name",
                    "type" => "string",
                    "length" => 255,
                ],
                Task::EVENT_PARAMS => [
                    "label" => "Event Params",
                    "description" => "Event params",
                    "name" => "event_params",
                    "type" => "json",
                ],
                Task::EVENT_STATUS => [
                    "label" => "Flag",
                    "description" => "Flag property",
                    "name" => "event_status",
                    "type" => "string",
                    "length" => 30,
                    "filters" => [
                        \Laminas\Filter\StringToLower::class => [],
                    ],
                    "validators" => [
                        \Laminas\Validator\StringLength::class => [
                            "min" => 1,
                            "max" => 30
                        ],
                    ],
                ],
                Task::TIMEOUT => [
                    "label" => "Timeout",
                    "description" => "Task timeout in seconds",
                    "name" => "timeout",
                    "type" => "integer",
                    "filters" => [
                        \Laminas\Filter\ToInt::class => [],
                    ],
                ],
            ],
            "types" => [
                Task::class => [
                    "database" => "marshal::scheduler",
                    "description" => "A scheduled task",
                    "name" => "Task",
                    "properties" => [
                        Content::ID,
                        Content::TAG,
                        Task::EVENT_NAME,
                        Task::EVENT_PARAMS,
                        Task::EVENT_STATUS,
                        Task::TIMEOUT,
                        Content::CREATED_AT,
                        Content::UPDATED_AT,
                    ],
                    "table" => "task",
                ],
            ],
        ];
    }

    private function getTemplatesConfig(): array
    {
        return [];
    }
}

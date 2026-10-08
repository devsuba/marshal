<?php

declare(strict_types=1);

namespace Marshal\Database\Handler;

use Marshal\Utils\Config;

trait ContentHandlerTrait
{
    private function getSelectedSchemaType(string $type): ?string
    {
        $schemaConfig = Config::get('schema');
        foreach ($schemaConfig['types'] ?? [] as $identifier => $config) {
            if (! isset($config['tag'])) {
                continue;
            }

            if ($config['tag'] !== $type) {
                continue;
            }

            return $identifier;
        }

        return null;
    }

    private function getSchemaConfig(string $schema): array
    {
        $databaseConfig = Config::get('database');
        foreach ($databaseConfig ?? [] as $dbConfig) {
            if (! isset($dbConfig['tag'])) {
                continue;
            }

            if ($dbConfig['tag'] !== $schema) {
                continue;
            }

            return $dbConfig;
        }

        return [];
    }

    private function getSchemaName(string $schema): ?string
    {
        $databaseConfig = Config::get('database');
        foreach ($databaseConfig ?? [] as $dbName => $dbConfig) {
            if (! isset($dbConfig['tag'])) {
                continue;
            }

            if ($dbConfig['tag'] !== $schema) {
                continue;
            }

            return $dbName;
        }

        return null;
    }
}

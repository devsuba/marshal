<?php

declare(strict_types=1);

namespace Marshal\Apps;

final class App
{
    public function __construct(private array $config)
    {
    }

    public function getContentSchema(): array
    {
        if (isset($this->config["schema"])) {
            return $this->config["schema"];
        }

        if (! isset($this->config["configFile"])) {
            return [];
        }

        $config = require $this->config["configFile"];
        return $config['schema']['types'] ?? [];
    }

    public function getContentSchemaType(string $identifier): ?string
    {
        $types = $this->getContentSchema();
        foreach ($types as $typeIdentifier => $type) {
            if (isset($type["tag"]) && $type["tag"] === $identifier) {
                return $typeIdentifier;
            }
        }

        return null;
    }

    public function getForms(): array
    {
        return $this->config['forms'] ?? [];
    }

    public function getTemplates(): array
    {
        return $this->config['templates'] ?? [];
    }

    public function toArray(): array
    {
        return [
            "label" => $this->config["label"],
            "description" => $this->config["description"],
            "tag" => $this->config["tag"],
        ];
    }
}

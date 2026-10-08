<?php

declare(strict_types=1);

namespace Marshal\Database\Query\Modifier;

use Marshal\Database\QueryBuilder;
use Marshal\Database\Schema\Content;
use Marshal\Utils\Logger\LoggerManager;

trait GroupBy
{
    private array $groupBy = [];

    public function groupBy(array|string $identifier): static
    {
        $this->groupBy[] = \is_array($identifier) ? \implode('__', $identifier) : $identifier;
        return $this;
    }

    private function applyGroupByExpressions(QueryBuilder $queryBuilder): void
    {
        foreach ($this->groupBy as $identifier) {
            if (FALSE !== \strpos($identifier, '__')) {
                [$relation, $property] = $this->applyGroupByRelationExpression($this->content, $identifier);
                $table = $relation->getAlias();
                $column = "{$relation->getAlias()}.{$property->getName()}";
            } else {
                if (! $this->content->hasProperty($identifier)) {
                    LoggerManager::get()->warning(\sprintf(
                        "Invalid where query identifier: Content %s has no property %s",
                        $this->content->getSchemaIdentifier(),
                        $identifier
                    ));
                    continue;
                }

                $table = $this->content->getTable();
                $property = $this->content->getProperty($identifier);
                $column = "{$table}.{$property->getName()}";
            }

            $queryBuilder->groupBy($column);
        }
    }

    private function applyGroupByRelationExpression(Content $content, string $identifier): array
    {
        $parts = explode('__', $identifier);
        $propertyIdentifier = \array_pop($parts);
        $relationIdentifier = \array_pop($parts);

        // basic 2 parts
        if (empty($parts)) {
            if (! $content->isRelationProperty($relationIdentifier)) {
                throw new \InvalidArgumentException(\sprintf(
                    "Invalid where identifier %s. %s is not a relation property of %s",
                    $identifier, $relationIdentifier, $content->getSchemaIdentifier()
                ));
            }

            $relation = $content->getRelation($relationIdentifier);
            $property = $relation->getRelationType()->getProperty($propertyIdentifier);
        } else {
            if (! $content->isRelationProperty($parts[0])) {
                throw new \InvalidArgumentException(\sprintf(
                    "Invalid where identifier %s. %s is not a relation property of %s",
                    $identifier, $parts[0], $content->getSchemaIdentifier()
                ));
            }

            while (\count($parts) > 0) {
                $nextRelationIdentifier = \array_shift($parts);
                if (\count($parts) === 0) {
                    $useType = isset($nextRelation) ? $nextRelation->getRelationType() : $content;
                    if (! $useType->isRelationProperty($nextRelationIdentifier)) {
                        throw new \InvalidArgumentException(\sprintf(
                            "Invalid where identifier %s. %s is not a relation property of %s",
                            $identifier, $nextRelationIdentifier, $content->getSchemaIdentifier()
                        ));
                    }

                    $nextRelation = $useType->getRelation($nextRelationIdentifier);
                    $relation = $nextRelation->getRelationType()->getRelation($relationIdentifier);
                    $property = $nextRelation->getRelationType()->getProperty($propertyIdentifier);
                } else {
                    $nextRelation = $content->getRelation($nextRelationIdentifier);

                    // @todo handle his block for > 3 relations
                }
            }
        }

        if (! isset($relation) || ! isset($property)) {
            throw new \RuntimeException(\sprintf(
                "Invalid where identifier %s. Relation not found",
                $identifier
            ));
        }

        return [$relation, $property];
    }
}

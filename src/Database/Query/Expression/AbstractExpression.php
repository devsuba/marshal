<?php

declare(strict_types=1);

namespace Marshal\Database\Query\Expression;

use Marshal\Database\Schema\Content;
use Marshal\Database\QueryBuilder;

abstract class AbstractExpression implements ExpressionInterface
{
    abstract public function apply(
        QueryBuilder $queryBuilder,
        Content $content,
        array|string $identifier,
        mixed $value
    ): void;

    protected function getColumnAndPropertyFromIdentifier(Content $content, array|string $identifier): array
    {
        if (\is_string($identifier)) {
            if (! $content->hasProperty($identifier)) {
                throw new \InvalidArgumentException(\sprintf(
                    "Invalid where query identifier: Content %s has no property %s",
                    $content->getSchemaIdentifier(),
                    $identifier
                ));
            }

            $property = $content->getProperty($identifier);
            return ["{$content->getTable()}.{$property->getName()}", $property];
        }

        return $this->getRelationColumnAndPropertyFromIdentifier($content, $identifier);
    }

    private function getRelationColumnAndPropertyFromIdentifier(Content $content, array $identifier): array
    {
        if (empty($identifier)) {
            throw new \InvalidArgumentException(\sprintf(
                "Invalid where relation identifier. Found empty identifier on content %s",
                $content->getSchemaIdentifier()
            ));
        }

        if (! $content->isRelationProperty($identifier[0])) {
            throw new \InvalidArgumentException(\sprintf(
                "Invalid where identifier %s is not a relation property of %s",
                $identifier[0], $content->getSchemaIdentifier()
            ));
        }

        if (\count($identifier) === 2) {
            $relation = $content->getRelation($identifier[0]);
            $property = $relation->getRelationType()->getProperty($identifier[1]);
        } else {
            $relationContent = $content->getRelation($identifier[0])->getRelationType();
            \array_shift($identifier);
            return $this->getRelationColumnAndPropertyFromIdentifier($relationContent, $identifier);
        }

        return ["{$relation->getAlias()}.{$property->getName()}", $property];
    }
}

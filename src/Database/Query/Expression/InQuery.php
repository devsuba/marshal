<?php

declare(strict_types=1);

namespace Marshal\Database\Query\Expression;

use Marshal\Database\Query\Select;
use Marshal\Database\QueryBuilder;
use Marshal\Database\Schema\Content;

final class InQuery extends AbstractExpression
{
    public function apply(
        QueryBuilder $queryBuilder,
        Content $content,
        array|string $identifier,
        mixed $value
    ): void {
        if (! $value instanceof Select) {
            throw new \InvalidArgumentException(\sprintf(
                "Invalid %s expression value. Expected instance of %s, given %s instead",
                self::class,
                Select::class,
                \get_debug_type($value)
            ));
        }

        $queryValues = [];
        foreach ($value->getPreparedQuery()->executeQuery()->iterateAssociative() as $row) {
            if (empty($row)) {
                continue;
            }

            $queryValues[] = \array_values($row)[0];
        }

        $column = $this->getColumnAndPropertyFromIdentifier($content, $identifier)[0];
        $queryBuilder->andWhere($queryBuilder->expr()->in(
            $column,
            $queryBuilder->createNamedParameter($queryValues)
        ));
    }
}

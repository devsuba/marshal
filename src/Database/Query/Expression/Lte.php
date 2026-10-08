<?php

declare(strict_types=1);

namespace Marshal\Database\Query\Expression;

use Marshal\Database\QueryBuilder;
use Marshal\Database\Schema\Content;

final class Lte extends AbstractExpression
{
    public function apply(
        QueryBuilder $queryBuilder,
        Content $content,
        array|string $identifier,
        mixed $value
    ): void {
        [$column, $property] = $this->getColumnAndPropertyFromIdentifier($content, $identifier);
        $queryBuilder->andWhere($queryBuilder->expr()->lte(
            $column,
            $queryBuilder->createNamedParameter(
                $property->setValue($value)->convertToDatabaseValue($queryBuilder->getDatabasePlatform()),
                $property->getDatabaseType()->getBindingType()
            )
        ));
    }
}

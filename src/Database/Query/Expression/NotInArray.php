<?php

declare(strict_types=1);

namespace Marshal\Database\Query\Expression;

use Marshal\Database\QueryBuilder;
use Marshal\Database\Schema\Content;

final class NotInArray extends AbstractExpression
{
    public function apply(
        QueryBuilder $queryBuilder,
        Content $content,
        array|string $identifier,
        mixed $value
    ): void {
        if (! \is_array($value)) {
            throw new \InvalidArgumentException(\sprintf(
                "%s expression expects an array value",
                self::class
            ));
        }

        foreach ($value as $row) {
            if (! \is_scalar($row)) {
                throw new \InvalidArgumentException(\sprintf(
                    "%s expects an array of scalar values",
                    self::class
                ));
            }
        }

        $column = $this->getColumnAndPropertyFromIdentifier($content, $identifier)[0];
        $queryBuilder->andWhere($queryBuilder->expr()->notIn(
            $column,
            \array_map(
                static fn ($property): string => "'$property'",
                $value
            )
        ));
    }
}

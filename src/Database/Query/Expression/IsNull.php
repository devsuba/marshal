<?php

declare(strict_types=1);

namespace Marshal\Database\Query\Expression;

use Marshal\Database\QueryBuilder;
use Marshal\Database\Schema\Content;

final class IsNull extends AbstractExpression
{
    public function apply(
        QueryBuilder $queryBuilder,
        Content $content,
        array|string $identifier,
        mixed $value
    ): void {
        if (! \is_bool($value)) {
            throw new \InvalidArgumentException(\sprintf(
                "%s expects a boolean value, %s given instead",
                self::class,
                \get_debug_type($value)
            ));
        }

        $column = $this->getColumnAndPropertyFromIdentifier($content, $identifier)[0];
        if (FALSE === $value) {
            $queryBuilder->andWhere($queryBuilder->expr()->isNotNull($column));
        } else {
            $queryBuilder->andWhere($queryBuilder->expr()->isNull($column));
        }
    }
}

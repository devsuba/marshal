<?php

declare(strict_types=1);

namespace Marshal\Database\Query\Expression;

use Marshal\Database\QueryBuilder;
use Marshal\Database\Schema\Content;

final class Raw extends AbstractExpression
{
    public function apply(
        QueryBuilder $queryBuilder,
        Content $content,
        array|string $identifier,
        mixed $value
    ): void {
        if (\is_array($identifier)) {
            throw new \InvalidArgumentException(\sprintf(
                "%s expects a string identifier, array given instead",
                self::class
            ));
        }

        if (! \is_array($value)) {
            throw new \InvalidArgumentException(\sprintf(
                "%s expects an array value, %s given instead",
                self::class,
                \get_debug_type($value)
            ));
        }

        $queryBuilder->andWhere($identifier);
        foreach ($value as $k => $v) {
            $value = $v instanceof Content ? $v->getAutoIncrement()->getValue() : $v;
            $queryBuilder->setParameter($k, $value);
        }
    }
}

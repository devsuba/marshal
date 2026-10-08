<?php

declare(strict_types=1);

namespace Marshal\Database\Query\Expression;

use Marshal\Database\QueryBuilder;
use Marshal\Database\Schema\Content;

final class Json extends AbstractExpression
{
    public function apply(
        QueryBuilder $queryBuilder,
        Content $content,
        array|string $identifier,
        mixed $value
    ): void {
    }
}

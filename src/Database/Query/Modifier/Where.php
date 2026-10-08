<?php

declare(strict_types=1);

namespace Marshal\Database\Query\Modifier;

use Marshal\Database\QueryBuilder;
use Marshal\Utils\Config;
use Marshal\Utils\Logger\LoggerManager;
use Marshal\Database\Schema\Content;
use Marshal\Database\Query\Expression\ExpressionInterface;

trait Where
{
    use WhereConstants;

    private array $where = [];

    public function where(
        array|string $identifier,
        mixed $value,
        string $expression = QueryBuilder::WHERE_EQ
    ): static {
        $this->where[] = [
            'identifier' => $identifier,
            'value' => $value,
            'expression' => $expression,
        ];

        return $this;
    }

    private function applyWhereExpressions(QueryBuilder $queryBuilder, Content $content): void
    {
        $expressions = Config::get('database_expressions')['where'];
        foreach ($this->where as $where) {
            if (! isset($expressions[$where['expression']])) {
                LoggerManager::get()->warning(\sprintf(
                    "Where expression %s not found in config",
                    $where['expression']
                ));
                continue;
            }

            if (! \class_exists($expressions[$where['expression']])) {
                throw new \InvalidArgumentException(\sprintf(
                    "Expression %s not found",
                    $where['expression']
                ));
            }

            try {
                $expr = new $expressions[$where['expression']];
            } catch (\Throwable $e) {
                throw new \InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
            }

            if (! $expr instanceof ExpressionInterface) {
                throw new \InvalidArgumentException(\sprintf(
                    "Expected expression to be an instance of %s, %s given instead",
                    ExpressionInterface::class,
                    \get_debug_type($expr)
                ));
            }

            $expr->apply($queryBuilder, $content, $where['identifier'], $where['value']);
        }
    }
}

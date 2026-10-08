<?php

declare(strict_types=1);

namespace Marshal\Database\Query\Expression;

use Marshal\Database\QueryBuilder;
use Marshal\Database\Schema\Content;
use Marshal\Database\Schema\Property;

final class Either extends AbstractExpression
{
    public function apply(
        QueryBuilder $queryBuilder,
        Content $content,
        array|string $identifier,
        mixed $value
    ): void {
        if (\is_string($identifier)) {
            throw new \InvalidArgumentException("Where OR identifier expects an array, string given");
        }

        if (! \is_array($value)) {
            throw new \InvalidArgumentException("Where OR value expects an array");
        }

        $where = "";
        $placeholders = [];
        $properties = [];
        foreach ($identifier as $row) {
            if (\count($row) === 1) {
                foreach ($row as $name => $placeholder) {
                    [$column, $property] = $this->getColumnAndPropertyFromIdentifier($content, $name);
                    $where .= "{$column} = :{$placeholder} OR ";
                    $placeholders[] = $placeholder;
                    $properties[$placeholder] = $property;
                }
            } else {
                $and = "(";
                foreach ($row as $name => $placeholder) {
                    [$column, $property] = $this->getColumnAndPropertyFromIdentifier($content, $name);
                    $and .= "{$column} = :{$placeholder} AND ";
                    $placeholders[] = $placeholder;
                    $properties[$placeholder] = $property;
                }
                $where .= \substr($and, 0, -5) . ") OR ";
            }
        }

        $queryBuilder->andWhere(\substr($where, 0, -4));
        foreach (\array_unique($placeholders) as $term) {
            foreach ($value as $placeholder => $item) {
                if ($term !== $placeholder) {
                    continue;
                }

                if (! isset($properties[$placeholder])) {
                    continue;
                }

                $property = $properties[$placeholder];
                \assert($property instanceof Property);
                $queryBuilder->createNamedParameter(
                    $property->setValue($item)->convertToDatabaseValue($queryBuilder->getDatabasePlatform()),
                    $property->getDatabaseType()->getBindingType(),
                    ":{$placeholder}"
                );
            }
        }
    }
}

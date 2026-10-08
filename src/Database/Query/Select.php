<?php

declare(strict_types=1);

namespace Marshal\Database\Query;

use Marshal\Database\Query\Modifier\GroupBy;
use Marshal\Database\Query\Modifier\Having;
use Marshal\Database\Query\Modifier\OrderBy;
use Marshal\Database\Query\Modifier\Where;
use Marshal\Database\QueryBuilder;
use Marshal\Database\Schema\Content;
use Marshal\Database\Schema\ContentManager;
use loophp\collection\Collection;
use Marshal\Database\Hydrator\ContentResultHydrator;
use Marshal\Database\Schema\Property;
use Marshal\Database\Schema\ContentRelation;

final class Select extends AbstractQuery
{
    use GroupBy;
    use Having;
    use OrderBy;
    use Where;

    private array $distinct = [];
    private array $excludeProperties = [];
    private array $excludeRelations = [];
    private ?int $limit = null;
    private int $offset = 0;
    private array $processedRelations = [];
    private array $properties = [];

    public function __construct(private Content $content)
    {
    }

    public static function from(string $from): static
    {
        return new self(ContentManager::get($from));
    }

    public function count(): int
    {
        return $this->fetchAllLazy()->count();
    }

    public function distinct(string $identifier, string $property): static
    {
        $this->distinct = [$identifier, $property];
        return $this;
    }

    public function excludeProperties(string $identifier, array $properties): static
    {
        $this->excludeProperties[$identifier] = $properties;
        return $this;
    }

    public function excludeProperty(string $identifier, string $property): static
    {
        $this->excludeProperties[$identifier][] = $property;
        return $this;
    }

    public function excludeRelations(string ...$identifier): static
    {
        foreach ($identifier as $relation) {
            $this->excludeRelations[] = $relation;
        }
        return $this;
    }

    public function fetch(): object
    {
        $this->limit(1);
        $query = $this->prepare();
        $result = $this->fetchArrayResult($query);

        if (! empty($result)) {
            $hydrator = new ContentResultHydrator();
            $hydrator->hydrate($this->content, $result, $query->getDatabasePlatform());
        }

        return $this->content;
    }

    public function fetchAllAssociative(): array
    {
        $query = $this->prepare();
        try {
            $result = $query
                ->executeQuery()
                ->fetchAllAssociative();
        } catch (\Throwable $e) {
            throw new Exception\DatabaseQueryException($e, $query);
        }

        return $result;
    }

    public function fetchAllLazy(bool $toArray = false): Collection
    {
        $query = $this->prepare();
        try {
            $iterable = $query
                ->executeQuery()
                ->iterateAssociative();
        } catch (\Throwable $e) {
            throw new Exception\DatabaseQueryException($e, $query);
        }

        $content = $this->content->getSchemaIdentifier();
        $platform = $query->getDatabasePlatform();

        return Collection::fromCallable(static function () use ($iterable, $toArray, $content, $platform): \Generator {
            $hydrator = new ContentResultHydrator();
            foreach ($iterable as $row) {
                $item = ContentManager::get($content);
                $hydrator->hydrate($item, $row, $platform);
                yield $toArray ? $item->toArray() : $item;
            }
        });
    }

    public function fetchAssociative(): array
    {
        $this->limit(1);
        return $this->fetchArrayResult($this->prepare());
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    public function properties(string $identifier, array $properties): static
    {
        $this->properties[$identifier] = $properties;
        return $this;
    }

    protected function prepare(): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder($this->content->getContentConfig()->getDatabase());
        $queryBuilder->from($this->content->getContentConfig()->getTable(), $this->content->getContentConfig()->getTable());

        $this->applyDistincts($queryBuilder, $this->content);
        $this->applyProperties($queryBuilder, $this->content);
        $this->applyRelations($queryBuilder, $this->content);
        $this->applyWhereExpressions($queryBuilder, $this->content);
        $this->applyGroupByExpressions($queryBuilder);
        $this->applyHavingExpressions($queryBuilder);
        $this->applyOrderByExpressions($queryBuilder);

        return $queryBuilder->setMaxResults($this->limit)->setFirstResult($this->offset);
    }

    private function applyDistincts(QueryBuilder $queryBuilder, Content $content): void
    {
        if (empty($this->distinct)) {
            return;
        }

        [$typeIdentifier, $propertyIdentifier] = $this->distinct;
        if ($content->getSchemaIdentifier() === $typeIdentifier || $content->getTable() === $typeIdentifier) {
            if (! $content->hasProperty($propertyIdentifier)) {
                throw new \InvalidArgumentException(\sprintf(
                    "Invalid distinct query: Property %s not found on content %s",
                    $propertyIdentifier, $typeIdentifier
                    ));
            }

            $this->applyTypeDistinctProperty($queryBuilder, $content, $propertyIdentifier);
            return;
        }

        if ($content->isRelationProperty($typeIdentifier)) {
            $relation = $content->getRelation($typeIdentifier);
            $this->applyTypeDistinctProperty($queryBuilder, $relation->getRelationType(), $propertyIdentifier, $relation->getAlias());
            // $this->applyRelationJoin($queryBuilder, $relation);
            return;

        }

        throw new \InvalidArgumentException(\sprintf(
            "Invalid distinct query. Unknown distinct identifier %s on content %s",
            $typeIdentifier, $content->getSchemaIdentifier()
            ));
    }

    private function isExcludedProperty(string $typeIdentifier, Property $property): bool
    {
        return \in_array($property->getIdentifier(), $this->excludeProperties[$typeIdentifier], true) ||
        \in_array($property->getName(), $this->excludeProperties[$typeIdentifier], true);
    }

    private function applyProperties(QueryBuilder $queryBuilder, Content $content, ?string $alias = null): void
    {
        $delta = empty($this->properties) && empty($this->distinct)
        ? [$content->getSchemaIdentifier() => \array_map(
            static fn (Property $property): string => $property->getName(),
            $content->getProperties()
        )]
        : $this->properties;

        foreach ($delta as $identifier => $properties) {
            if ($content->isRelationProperty($identifier)) {
                $relation = $content->getRelation($identifier);
                $this->applyTypeProperties(
                    $queryBuilder,
                    $relation->getRelationType(),
                    $properties,
                    $relation->getAlias()
                );
                continue;
            }

            if ($content->hasProperty($identifier)) {
                $this->applyTypeProperties($queryBuilder, $content, $properties, $alias);
                $this->excludeProperties($alias ?? $identifier, $properties);
                continue;
            }

            if (
                $identifier === $content->getSchemaIdentifier() ||
                $identifier === $content->getTable()
            ) {
                $this->applyTypeProperties($queryBuilder, $content, $properties, $alias);
                $this->excludeProperties($alias ?? $identifier, $properties);
                continue;
            }

            throw new \InvalidArgumentException(\sprintf(
                "Invalid query. Unknown properties identifier %s on content %s",
                $identifier, $content->getSchemaIdentifier()
            ));
        }
    }

    private function applyTypeDistinctProperty(QueryBuilder $queryBuilder, Content $content, string $identifier, ?string $alias = null): void
    {
        if (! $content->hasProperty($identifier) && null === $alias) {
            throw new \InvalidArgumentException(\sprintf(
                "Distinct property %s not found on type %s",
                $identifier, $content->getSchemaIdentifier()
                ));
        }

        // add the qualfied select
        $table = $alias ?? $content->getTable();
        $name = $content->getProperty($identifier)->getName();
        $queryBuilder->addSelect("DISTINCT {$table}.$name AS {$table}__$name");

        // exclude property from further processing
        $this->excludeProperty($content->getSchemaIdentifier(), $identifier);
    }

    private function applyTypeProperties(QueryBuilder $queryBuilder, Content $content, array $properties, ?string $alias = null): void
    {
        foreach ($properties as $identifier) {
            if (! $content->hasProperty($identifier)) {
                throw new \InvalidArgumentException(\sprintf(
                    "Property %s not found on type %s",
                    $identifier, $content->getSchemaIdentifier()
                    ));
            }

            $property = $content->getProperty($identifier);
            // skip excluded properties by type identifier
            if (\array_key_exists($content->getSchemaIdentifier(), $this->excludeProperties)) {
                if (
                    \in_array($property->getIdentifier(), $this->excludeProperties[$content->getSchemaIdentifier()], true) ||
                    \in_array($property->getName(), $this->excludeProperties[$content->getSchemaIdentifier()], true)
                    ) {
                        continue;
                    }
            }

            // skip excluded properties by type table name
            if (\array_key_exists($content->getTable(), $this->excludeProperties)) {
                if (
                    \in_array($property->getIdentifier(), $this->excludeProperties[$content->getTable()], true) ||
                    \in_array($property->getName(), $this->excludeProperties[$content->getTable()], true)
                    ) {
                        continue;
                    }
            }

            $table = $alias ?? $content->getTable();
            if ($content->isRelationProperty($property->getIdentifier())) {
                $table = $content->getRelation($property->getIdentifier())->getAlias();
            }

            // add the select
            if ($content->isRelationProperty($property->getIdentifier())) {
                $relation = $content->getRelation($property->getIdentifier());
                $this->applyProperties($queryBuilder, $relation->getRelationType(), $relation->getAlias());
            } else {
                $queryBuilder->addSelect("{$table}.{$property->getName()} AS {$table}__{$property->getName()}");
            }
        }
    }

    private function applyRelations(QueryBuilder $queryBuilder, Content $content): void
    {
        // @todo use query properties to apply relations selectively
        foreach ($content->getRelations() as $relation) {
            \assert($relation instanceof ContentRelation);

            // @todo review and test these exclusions
            if ($this->isExcludedRelation($relation)) {
                continue;
            }

            // apply relation joins
            $this->applyRelationJoin($queryBuilder, $relation, $content);

            // repeat the process for nested relations
            $this->processedRelations[] = $relation->getAlias();
            $this->applyRelations($queryBuilder, $relation->getRelationType());
        }
    }

    private function applyRelationJoin(QueryBuilder $queryBuilder, ContentRelation $relation, Content $content): void
    {
        switch ($relation->getJoinType()) {
            case ContentRelation::JOIN_INNER:
                $queryBuilder->innerJoin(
                    fromAlias: $this->content->getTable(),
                    join: $relation->getRelationType()->getTable(),
                    alias: $relation->getAlias(),
                    condition: $relation->getRelationCondition($content)
                );
                break;

            case ContentRelation::JOIN_RIGHT:
                $queryBuilder->rightJoin(
                    fromAlias: $this->content->getTable(),
                    join: $relation->getRelationType()->getTable(),
                    alias: $relation->getAlias(),
                    condition: $relation->getRelationCondition($content)
                );
                break;

            case ContentRelation::JOIN_LEFT:
                $queryBuilder->leftJoin(
                    fromAlias: $this->content->getTable(),
                    join: $relation->getRelationType()->getTable(),
                    alias: $relation->getAlias(),
                    condition: $relation->getRelationCondition($content)
                );
                break;

            default:
                throw new \InvalidArgumentException(\sprintf(
                    "Invalid join type %s on relation %s",
                    $relation->getJoinType(),
                    $relation->getIdentifier()
                ));
        }
    }

    private function isExcludedRelation(ContentRelation $relation): bool
    {
        return \in_array($relation->getIdentifier(), $this->excludeRelations, true) ||
            \in_array($relation->getRelationType()->getTable(), $this->excludeRelations, true) ||
            \in_array($relation->getAlias(), $this->excludeRelations, true) ||
            \in_array($relation->getAlias(), $this->processedRelations, true) ||
            \in_array('*', $this->excludeRelations, true);
    }

    private function fetchArrayResult(QueryBuilder $query): array
    {
        try {
            $result = $query
                ->executeQuery()
                ->fetchAssociative();
        } catch (\Throwable $e) {
            throw new Exception\DatabaseQueryException($e, $query);
        }

        return \is_array($result) ? $result : [];
    }
}

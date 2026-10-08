<?php

declare(strict_types=1);

namespace Marshal\Platform\Web\Page;

use loophp\collection\Collection;
use Marshal\Database\Query\Create;
use Marshal\Database\Query\Delete;
use Marshal\Database\Query\Select;
use Marshal\Database\Query\Update;
use Marshal\Database\Schema\Content;

final class PageRepository
{
    public static function fetchPage(string $tag): Page
    {
        return Select::from(Page::class)
            ->where(Content::TAG, $tag)
            ->fetch();
    }

    public static function fetchPages(int $offset = 0, int $limit = 10, string $orderBy = Content::CREATED_AT): Collection
    {
        return Select::from(Page::class)
            ->offset($offset)
            ->limit($limit)
            ->orderBy($orderBy)
            ->fetchAllLazy();
    }

    public static function removePage(Page $page): void
    {
        Delete::target($page);
    }

    public static function saveNewPage(): void
    {
    }

    public static function updatePage(Page $page, array $updates): void
    {
        Update::target($page)->withValues($updates);
    }
}

<?php

declare(strict_types=1);

namespace Marshal\Platform\Web\Page;

use Marshal\Database\Schema\Content;

final class Page extends Content
{
    public const string BODY = "marshal::page-body";
    public const string LAYOUT = "marshal::page-layout";
    public const string META = "marshal::page-meta";
    public const string TITLE = "marshal::page-title";

    private function getBody(): string
    {
        return $this->getPropertyValue(self::BODY);
    }

    private function getLayout(): string
    {
        return $this->getPropertyValue(self::LAYOUT);
    }

    private function getMeta(): string
    {
        return $this->getPropertyValue(self::META);
    }

    private function getTitle(): string
    {
        return $this->getPropertyValue(self::TITLE);
    }

    private function getUrl(): string
    {
        return $this->getPropertyValue(Content::URL);
    }
}

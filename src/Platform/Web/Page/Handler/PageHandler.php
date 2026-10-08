<?php

declare(strict_types=1);

namespace Marshal\Platform\Web\Page\Handler;

use Marshal\Platform\PlatformInterface;
use Marshal\Platform\Web\Page\PageRepository;
use Marshal\Utils\Helper\ServerRequestHelperTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class PageHandler implements RequestHandlerInterface
{
    use ServerRequestHelperTrait;

    public const string PAGE_INDEX = "marshal::page-index";
    public const string PAGE_CREATE = "marshal::page-create";
    public const string PAGE_REMOVE = "marshal::page-remove";
    public const string PAGE_UPDATE = "marshal::page-update";
    public const string PAGE_VIEW = "marshal::page-view";

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $platform = $this->getRequestPlatform($request);
        $routeResult = $this->getRouteResult($request);
        return match ($routeResult->getMatchedRouteName()) {
            self::PAGE_INDEX => $this->handlePageIndex($request, $platform),
            self::PAGE_CREATE => $this->handlePageCreate($request, $platform),
            self::PAGE_REMOVE => $this->handlePageRemove($request, $platform),
            self::PAGE_UPDATE => $this->handlePageUpdate($request, $platform),
            self::PAGE_VIEW => $this->handlePageView($request, $platform),
            default => $platform->badRequestResponse($request)
        };
    }

    private function handlePageCreate(ServerRequestInterface $request, PlatformInterface $platform): ResponseInterface
    {
        $pages = PageRepository::fetchPages();
        return $platform->formatResponse($request, [
            "data" => $pages,
        ], self::PAGE_INDEX);
    }

    private function handlePageIndex(ServerRequestInterface $request, PlatformInterface $platform): ResponseInterface
    {
        $pages = PageRepository::fetchPages();
        return $platform->formatResponse($request, [
            "data" => $pages,
        ], self::PAGE_INDEX);
    }

    private function handlePageRemove(ServerRequestInterface $request, PlatformInterface $platform): ResponseInterface
    {
        $pages = PageRepository::fetchPages();
        return $platform->formatResponse($request, [
            "data" => $pages,
        ], self::PAGE_INDEX);
    }

    private function handlePageUpdate(ServerRequestInterface $request, PlatformInterface $platform): ResponseInterface
    {
        $params = $request->getQueryParams();
        $page = PageRepository::fetchPage($params["tag"]);

        return $platform->formatResponse($request, [
            'page' => $page->toArray(),
        ], self::PAGE_VIEW);
    }

    private function handlePageView(ServerRequestInterface $request, PlatformInterface $platform): ResponseInterface
    {
        $params = $request->getQueryParams();
        $page = PageRepository::fetchPage($params["tag"]);

        return $platform->formatResponse($request, [
            'page' => $page->toArray(),
        ], self::PAGE_VIEW);
    }

    private function handleContentView(ServerRequestInterface $request, PlatformInterface $platform, Content $content): ResponseInterface
    {
        $params = $request->getQueryParams();
        if (empty($params) || ! isset($params['tag'])) {
            return $this->handleContentViewIndex($request, $platform, $content);
        }

        // hydrate the content
        $content = $this->hydrateContent($request, $content);

        // check whether content was hydrated, i.e found
        if ($content->isEmpty()) {
            return $platform->notFoundResponse($request);
        }

        // view an edit page
        if (isset($params['action']) && $params['action'] === "edit" && 'GET' === \strtoupper($request->getMethod())) {
            return $this->handleContentEditView($request, $platform, $content);
        }

        // updating content
        if (isset($params['action']) && $params['action'] === "edit" && 'POST' === \strtoupper($request->getMethod())) {
            return $this->handleContentUpdate($request, $platform, $content);
        }

        $template = $content->getContentConfig()->hasViewTemplate()
            ? $content->getContentConfig()->getViewTemplate()
            : self::APP_CONTENT_TYPE;

        return $platform->formatResponse(
            $request,
            ["content" => $content->toArray()],
            $template
        );
    }

    private function handleContentViewIndex(ServerRequestInterface $request, PlatformInterface $platform, Content $content): ResponseInterface
    {
        return $platform->formatResponse(
            $request
        );
    }

    private function handleContentEditView(
        ServerRequestInterface $request,
        PlatformInterface $platform,
        Content $content
    ): ResponseInterface {
        $form = ContentForm::create($content);
        $form->setData($content->toArray());

        // @todo form handling

        return $platform->formatResponse($request);
    }

    private function handleContentUpdate(
        ServerRequestInterface $request,
        PlatformInterface $platform,
        Content $content
    ): ResponseInterface {
        $form = ContentForm::create($content);
        $form->setData($content->toArray());

        // @todo handle form

        return $platform->formatResponse($request);
    }

    private function hydrateContent(ServerRequestInterface $request, Content $content): Content
    {
        $select = Select::from($content->getSchemaIdentifier());
        $params = $request->getQueryParams();
        foreach ($params as $key => $value) {
            if (! $content->hasProperty($key)) {
                continue;
            }

            if ($content->isRelationProperty($key)) {
                continue;
            }

            $select->where($key, $value);
        }
        
        return $select->fetch();
    }
}

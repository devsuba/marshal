<?php

declare(strict_types=1);

namespace Marshal\Database\Handler;

use Marshal\Database\Query\Select;
use Marshal\Database\Schema\Content;
use Marshal\Database\Schema\ContentForm;
use Marshal\Database\Schema\ContentManager;
use Marshal\Platform\PlatformInterface;
use Marshal\Utils\Helper\ServerRequestHelperTrait;
use Marshal\Utils\Logger\LoggerManager;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ContentHandler implements RequestHandlerInterface
{
    use ServerRequestHelperTrait;

    public const string CONTENT_DASHBOARD = "marshal::content-dashboard";
    public const string CONTENT_SCHEMA = "marshal::content-schema";
    public const string CONTENT_SCHEMA_TYPE = "marshal::content-schema-type";

    public function __construct(private ContainerInterface $container, private array $databaseConfig, private array $schemaConfig)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeResult = $this->getRouteResult($request);
        $platform = $this->getRequestPlatform($request);
        return match ($routeResult->getMatchedRouteName()) {
            self::CONTENT_DASHBOARD => $this->handleContentDashboard($request, $platform),
            self::CONTENT_SCHEMA => $this->handleContentSchema($request, $platform),
            self::CONTENT_SCHEMA_TYPE => $this->handleContentSchemaType($request),
            default => $platform->badRequestResponse($request),
        };
    }

    private function getSelectedSchemaType(string $type, array $schemaConfig): ?string
    {
        foreach ($schemaConfig['types'] as $identifier => $config) {
            if (! isset($config['tag'])) {
                continue;
            }

            if ($config['tag'] !== $type) {
                continue;
            }

            return $identifier;
        }

        return null;
    }

    private function getSchemaConfig(string $schema, array $databaseConfig): array
    {
        foreach ($databaseConfig as $dbConfig) {
            if (! isset($dbConfig['tag'])) {
                continue;
            }

            if ($dbConfig['tag'] !== $schema) {
                continue;
            }

            return $dbConfig;
        }

        return [];
    }

    private function getSchemaName(string $schema, array $databaseConfig): ?string
    {
        foreach ($databaseConfig as $dbName => $dbConfig) {
            if (! isset($dbConfig['tag'])) {
                continue;
            }

            if ($dbConfig['tag'] !== $schema) {
                continue;
            }

            return $dbName;
        }

        return null;
    }

    private function handleContentDashboard(ServerRequestInterface $request, PlatformInterface $platform): ResponseInterface
    {
        $data = [];
        foreach ($this->databaseConfig as $database) {
            if (isset($database['system']) && true === $database['system']) {
                continue;
            }

            if (! isset($database['tag'])) {
                continue;
            }

            $data[$database['tag']] = [
                'name' => $database['label'],
            ];
        }

        return $platform->formatResponse($request, [
            'data' => $data,
        ], self::CONTENT_DASHBOARD);
    }

    private function handleContentSchema(ServerRequestInterface $request, PlatformInterface $platform): ResponseInterface
    {
        $schema = $request->getAttribute('schema');
        $selectedDbName = $this->getSchemaName($schema, $this->databaseConfig);
        $selectedDbConfig = $this->getSchemaConfig($schema, $this->databaseConfig);

        if (null === $selectedDbName || empty($selectedDbConfig)) {
            return $platform->notFoundResponse($request);
        }

        $data = [];
        foreach ($this->schemaConfig['types'] ?? [] as $type) {
            if (! isset($type['database']) || ! isset($type['tag'])) {
                continue;
            }

            if ($type['database'] !== $selectedDbName) {
                if (! isset($selectedDbConfig['tag']) || $type['database'] !== $selectedDbConfig['tag']) {
                    continue;
                }
            }

            $data[$type['tag']] = [
                'name' => $type['name'],
                'description' => $type['description'],
            ];
        }

        return $platform->formatResponse($request, [
            'schema' => $selectedDbConfig,
            'data' => $data,
        ], "marshal::content-schema");
    }

    public function handleContentSchemaType(ServerRequestInterface $request): ResponseInterface
    {
        $platform = $this->getRequestPlatform($request);
        $schema = $request->getAttribute('schema');
        $type = $request->getAttribute('type');

        $selectedDbName = $this->getSchemaName($schema, $this->databaseConfig);
        $selectedDbConfig = $this->getSchemaConfig($schema, $this->databaseConfig);
        $selectedSchemaType = $this->getSelectedSchemaType($type, $this->schemaConfig);

        if (null === $selectedDbName || empty($selectedDbConfig) || empty($selectedSchemaType)) {
            return $platform->notFoundResponse($request);
        }

        $content = ContentManager::get($selectedSchemaType);
        if (null === $content->getContentConfig()->getHandler()) {
            return $this->handleContentView($request, $platform, $content);
        }

        $handler = $this->container->get($content->getContentConfig()->getHandler());
        if (! $handler instanceof RequestHandlerInterface) {
            LoggerManager::get()->error("Invalid handler");
            return $this->handleContentView($request, $platform, $content);
        }

        return $handler->handle($request);
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
            : self::CONTENT_SCHEMA_TYPE;

        return $platform->formatResponse(
            $request,
            ["content" => $content->toArray()],
            $template
        );
    }

    private function handleContentViewIndex(ServerRequestInterface $request, PlatformInterface $platform, Content $content): ResponseInterface
    {
        $collection = Select::from($content->getSchemaIdentifier())
            ->orderBy(Content::ID, 'DESC')
            ->limit(450);
        $template = $content->getContentConfig()->hasIndexTemplate()
            ? $content->getContentConfig()->getIndexTemplate()
            : self::CONTENT_SCHEMA_TYPE;

        return $platform->formatResponse(
            $request,
            ["collection" => $collection->fetchAllLazy(true)],
            $template
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

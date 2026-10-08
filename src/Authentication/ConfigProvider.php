<?php

declare(strict_types=1);

namespace Marshal\Authentication;

use Marshal\Authentication\User\User;
use Marshal\Authentication\User\UserFactory;
use Marshal\Authentication\User\UserInterface;
use Marshal\Database\Schema\Content;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            "dependencies" => $this->getDependencies(),
            "navigation" => $this->getNavigationConfig(),
            "schema" => $this->getSchemaConfig(),
            "session" => $this->getSessionConfig(),
            "templates" => $this->getTemplatesConfig(),
        ];
    }

    private function getDependencies(): array
    {
        return [
            "factories" => [
                AuthenticationMiddleware::class => AuthenticationMiddlewareFactory::class,
                Handler\AuthenticationHandler::class => Handler\AuthenticationHandlerFactory::class,
                \PSR7Sessions\Storageless\Http\SessionMiddleware::class => Session\SessionMiddlewareFactory::class,
                UserInterface::class => UserFactory::class,
            ],
        ];
    }

    private function getNavigationConfig(): array
    {
        return [
            "paths" => [
                '/login' => [
                    'methods' => ["GET", "POST"],
                    'middleware' => Handler\AuthenticationHandler::class,
                    'name' => Handler\AuthenticationHandler::LOGIN_PAGE,
                ],
                '/logout' => [
                    'methods' => ['GET', 'POST'],
                    'middleware' => Handler\AuthenticationHandler::class,
                    'name' => Handler\AuthenticationHandler::HANDLE_LOGOUT,
                ],
                "/settings" => [
                    "methods" => ["GET"],
                    "middleware" => Handler\Settings\SettingsDashboard::class,
                    "name" => Handler\Settings\SettingsDashboard::ROUTE_NAME,
                    "options" => [
                        "template" => "marshal::settings-dashboard",
                    ],
                ],
                "/settings/extensions" => [
                    "methods" => ["GET"],
                    "middleware" => Handler\Settings\Extensions\ExtensionsDashboard::class,
                    "name" => Handler\Settings\Extensions\ExtensionsDashboard::ROUTE_NAME,
                    "options" => [
                        "template" => "marshal::extensions-dashboard",
                    ],
                ],
            ],
        ];
    }

    private function getSchemaConfig(): array
    {
        return [
            "properties" => [
                "name" => [
                    "type" => "string",
                    "notnull" => true,
                    "length" => 255,
                    "constraints" => [
                        "unique" => true,
                    ],
                ],
                UserInterface::USER_CREDENTIAL => [
                    "description" => "Email address or phone number",
                    "name" => "credential",
                    "label" => "Credential",
                    "type" => "string",
                    "notnull" => true,
                    "length" => 255,
                    "comment" => "Email address or phone number",
                ],
                "login_type" => [
                    "type" => "string",
                    "notnull" => true,
                    "length" => 255,
                ],
                "first_name" => [
                    "type" => "string",
                    "length" => 255,
                ],
                "last_name" => [
                    "type" => "string",
                    "length" => 255,
                ],
                UserInterface::USER_PASSWORD => [
                    "description" => "User password",
                    "label" => "Password",
                    "name" => "password",
                    "type" => "string",
                    "length" => 255,
                ],
                UserInterface::USER_ROLES => [
                    "description" => "User roles",
                    "label" => "Roles",
                    "name" => "roles",
                    "type" => "json"
                ],
                UserInterface::USER_STATUS => [
                    "description" => "User status",
                    "label" => "Status",
                    "name" => "status",
                    "type" => "string",
                    "notnull" => true,
                    "default" => "active",
                    "length" => 255,
                ],
                "preferences" => [
                    "type" => "json"
                ],
                "meta" => [
                    "type" => "json"
                ],
                "last_seen" => [
                    "type" => "datetimetz_immutable",
                ],
            ],
            "types" => [
                User::class => [
                    "database" => "marshal::user",
                    "description" => "User content",
                    "name" => "User",
                    "properties" => [
                        Content::ID,
                        UserInterface::USER_CREDENTIAL,
                        UserInterface::USER_ROLES,
                        UserInterface::USER_PASSWORD,
                        UserInterface::USER_STATUS,
                        Content::CREATED_AT,
                        Content::UPDATED_AT
                    ],
                    "table" => "user",
                ],
            ],
        ];
    }

    private function getSessionConfig(): array
    {
        return [
            "cache_expire" => 10800,
            "cache_limiter" => "nocache",
            "name" => "marshal-session",
            "cookie" => [
                // "domain" => null,
                "lifetime" => 10800,
                "http_only" => true,
                "path" => "/",
                "samesite" => "Lax",
                "secure" => true,
            ],
        ];
    }

    private function getTemplatesConfig(): array
    {
        return [
            Handler\AuthenticationHandler::TEMPLATE_LOGIN_PAGE => [
                "filename" => __DIR__ . '/../../template/user/login.twig.html',
            ],
            "marshal::profile-dashboard" => [
                "filename" => "/main/profile/dashboard.twig.html",
                "includes" => ["main::layout"],
            ],
        ];
    }
}

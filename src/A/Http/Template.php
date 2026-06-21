<?php

declare(strict_types=1);

namespace A\Http;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Template
{
    public function __construct(protected string $path = '')
    {
    }

    public function resolve(mixed $data, \ReflectionMethod $method, object $controller) : Response
    {
        $class = $method->getDeclaringClass();
        $file = (string)$class->getFileName();
        $path = $this->path !== ''
            ? dirname($file) . '/' . $this->path
            : dirname($file) . '/' . basename($file, '.php') . '.phtml';

        if (!is_file($path))
        {
            throw new \RuntimeException("Template file not found: {$path}");
        }

        $helpers = $this->helpers();

        $render = function () use ($data, $path, $helpers): array {
            $layout = null;

            extract($helpers, EXTR_SKIP);

            if (is_array($data))
            {
                extract($data, EXTR_SKIP);
            }
            else
            {
                $value = $data;
            }

            ob_start();
            include $path;

            return [
                'body' => (string)ob_get_clean(),
                'layout' => $layout,
            ];
        };

        $result = $render->call($controller);
        $body = $this->layout((string)$result['body'], $result['layout'] ?? null, $controller, dirname($path));

        return new Response(
            headers: ['Content-Type' => 'text/html; charset=utf-8'],
            body: $body,
        );
    }

    private function layout(string $body, mixed $layout, object $controller, string $directory) : string
    {
        if (is_string($layout))
        {
            return $this->layout_file($body, ['file' => $layout], $controller, $directory);
        }

        if (is_array($layout) && isset($layout['file']))
        {
            return $this->layout_file($body, $layout, $controller, $directory);
        }

        if (!is_array($layout))
        {
            return $body;
        }

        $render = function () use ($layout, $body): string {
            return match ((string)($layout['type'] ?? '')) {
                'public' => $this->page(
                    (string)($layout['active'] ?? ''),
                    (string)($layout['title'] ?? ''),
                    (string)($layout['lead'] ?? ''),
                    is_array($layout['cards'] ?? null) ? $layout['cards'] : [],
                    $body,
                ),
                'dashboard' => $this->dashboard_page(
                    (string)($layout['active'] ?? ''),
                    (string)($layout['title'] ?? ''),
                    (string)($layout['subtitle'] ?? ''),
                    $body,
                    is_array($layout['user'] ?? null) ? $layout['user'] : [],
                ),
                'admin' => $this->admin_page(
                    (string)($layout['active'] ?? ''),
                    (string)($layout['title'] ?? ''),
                    $body,
                ),
                'bare' => $this->bare_page(
                    (string)($layout['title'] ?? ''),
                    (string)($layout['subtitle'] ?? ''),
                    $body,
                ),
                default => $body,
            };
        };

        return $render->call($controller);
    }

    private function layout_file(string $body, array $layout, object $controller, string $directory) : string
    {
        $file = (string)($layout['file'] ?? '');
        $path = $this->layout_path($file, $directory);

        if (!is_file($path))
        {
            throw new \RuntimeException("Layout file not found: {$path}");
        }

        $helpers = $this->helpers();

        $render = function () use ($layout, $body, $path, $helpers): string {
            extract($helpers, EXTR_SKIP);

            extract($layout, EXTR_SKIP);

            ob_start();
            include $path;

            return (string)ob_get_clean();
        };

        return $render->call($controller);
    }

    private function helpers() : array
    {
        $html = static fn (mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return [
            'html' => $html,
            'lines' => static fn (mixed $value): string => nl2br($html($value)),
            'url' => static fn (mixed $value): string => rawurlencode((string)$value),
        ];
    }

    private function layout_path(string $file, string $directory) : string
    {
        if ($file === '')
        {
            return '';
        }

        if (str_starts_with($file, '/') || preg_match('/^[A-Z]:[\\\\\/]/i', $file))
        {
            return $file;
        }

        return $directory . '/' . $file;
    }
}

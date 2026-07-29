<?php

namespace App\Core;

class Request
{
    private array $get;
    private array $post;
    private array $server;
    private array $files;
    private ?array $json = null;

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    public function isAjax(): bool
    {
        if (strtolower($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {
            return true;
        }

        $accept = strtolower((string) ($this->header('Accept') ?? ''));
        if (str_contains($accept, 'application/json')) {
            return true;
        }

        // FormData AJAX posts from RootsApp always send this custom header.
        if (!empty($this->server['HTTP_X_CSRF_TOKEN']) && str_contains($accept, 'application/json')) {
            return true;
        }

        return false;
    }

    public function path(): string
    {
        $uri = parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $script = dirname($this->server['SCRIPT_NAME'] ?? '');
        if ($script !== '/' && $script !== '\\' && str_starts_with($uri, $script)) {
            $uri = substr($uri, strlen($script)) ?: '/';
        }
        return '/' . trim($uri, '/');
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $all = array_merge($this->get, $this->post, $this->json() ?? []);
        return $all[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->get, $this->post, $this->json() ?? []);
    }

    public function only(array $keys): array
    {
        $all = $this->all();
        $out = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $all)) {
                $out[$key] = $all[$key];
            }
        }
        return $out;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->get[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function header(string $key): ?string
    {
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $this->server[$serverKey] ?? null;
    }

    public function json(): ?array
    {
        if ($this->json === null) {
            $raw = file_get_contents('php://input');
            if ($raw) {
                $decoded = json_decode($raw, true);
                $this->json = is_array($decoded) ? $decoded : [];
            } else {
                $this->json = [];
            }
        }
        return $this->json;
    }

    public function bearerToken(): ?string
    {
        $header = $this->server['HTTP_AUTHORIZATION'] ?? $this->server['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
            return $m[1];
        }
        return null;
    }
}

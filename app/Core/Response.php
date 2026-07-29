<?php

namespace App\Core;

class Response
{
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success(string $message = 'Success', mixed $data = null, int $status = 200): never
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ], $status);
    }

    public static function error(string $message = 'Unable to process your request.', mixed $errors = null, int $status = 422): never
    {
        self::json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $status);
    }

    public static function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    public static function view(string $view, array $data = [], ?string $layout = 'layouts/app'): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = App::basePath('resources/views/' . str_replace('.', '/', $view) . '.php');

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View [{$view}] not found.");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout) {
            $layoutFile = App::basePath('resources/views/' . str_replace('.', '/', $layout) . '.php');
            require $layoutFile;
        } else {
            echo $content;
        }
    }
}

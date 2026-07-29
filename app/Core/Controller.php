<?php

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = [], ?string $layout = 'layouts/app'): void
    {
        Response::view($view, $data, $layout);
    }

    protected function jsonSuccess(string $message = 'Success', mixed $data = null, int $status = 200): never
    {
        Response::success($message, $data, $status);
    }

    protected function jsonError(string $message = 'Unable to process your request.', mixed $errors = null, int $status = 422): never
    {
        Response::error($message, $errors, $status);
    }

    protected function redirect(string $path): never
    {
        Response::redirect(App::url(ltrim($path, '/')));
    }

    protected function validate(Request $request, array $rules): array
    {
        $data = $request->all();
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $ruleString) {
            $ruleList = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $value = $data[$field] ?? null;
            $label = ucwords(str_replace('_', ' ', $field));

            foreach ($ruleList as $rule) {
                if ($rule === 'required' && ($value === null || $value === '')) {
                    $errors[$field][] = "{$label} is required.";
                }
                if ($rule === 'email' && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = "{$label} must be a valid email.";
                }
                if (str_starts_with($rule, 'min:') && is_string($value) && strlen($value) < (int) substr($rule, 4)) {
                    $errors[$field][] = "{$label} must be at least " . substr($rule, 4) . " characters.";
                }
                if (str_starts_with($rule, 'max:') && is_string($value) && strlen($value) > (int) substr($rule, 4)) {
                    $errors[$field][] = "{$label} may not be greater than " . substr($rule, 4) . " characters.";
                }
                if ($rule === 'numeric' && $value !== null && $value !== '' && !is_numeric($value)) {
                    $errors[$field][] = "{$label} must be numeric.";
                }
                if (str_starts_with($rule, 'in:')) {
                    $allowed = explode(',', substr($rule, 3));
                    if ($value !== null && $value !== '' && !in_array((string) $value, $allowed, true)) {
                        $errors[$field][] = "{$label} is invalid.";
                    }
                }
                if (str_starts_with($rule, 'unique:')) {
                    // unique:table,column,ignoreId
                    $parts = explode(',', substr($rule, 7));
                    $table = $parts[0];
                    $column = $parts[1] ?? $field;
                    $ignoreId = $parts[2] ?? null;
                    if ($value !== null && $value !== '') {
                        $sql = "SELECT id FROM `{$table}` WHERE `{$column}` = ?";
                        $params = [$value];
                        if ($ignoreId) {
                            $sql .= " AND id != ?";
                            $params[] = $ignoreId;
                        }
                        if (Database::fetch($sql, $params)) {
                            $errors[$field][] = "{$label} already exists.";
                        }
                    }
                }
            }

            $validated[$field] = $value;
        }

        if ($errors) {
            if ($request->isAjax()) {
                Response::error('Please fix the validation errors.', $errors, 422);
            }
            Session::flash('errors', $errors);
            Session::flash('old', $data);
            $referer = $_SERVER['HTTP_REFERER'] ?? App::url('dashboard');
            Response::redirect($referer);
        }

        return $validated;
    }
}

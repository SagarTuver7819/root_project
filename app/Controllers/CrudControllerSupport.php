<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Services\AuditService;

trait CrudControllerSupport
{
    protected function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    protected function nextCode(string $table, string $column, string $prefix, int $pad = 5): string
    {
        $rows = Database::fetchAll(
            "SELECT {$column} AS code FROM {$table} WHERE {$column} LIKE ?",
            [$prefix . '%']
        );
        $max = 0;
        $pattern = '/^' . preg_quote($prefix, '/') . '(\d+)$/';
        foreach ($rows as $row) {
            $code = (string) ($row['code'] ?? '');
            if (preg_match($pattern, $code, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }
        return $prefix . str_pad((string) ($max + 1), $pad, '0', STR_PAD_LEFT);
    }

    protected function options(string $table, string $label = 'name', string $extraWhere = ''): array
    {
        $where = 'deleted_at IS NULL';
        if ($extraWhere !== '') {
            $where .= ' AND ' . $extraWhere;
        }
        return Database::fetchAll("SELECT id, {$label} AS name FROM {$table} WHERE {$where} ORDER BY {$label}");
    }

    protected function audit(string $module, string $action, ?int $recordId = null, mixed $old = null, mixed $new = null): void
    {
        AuditService::log($module, $action, $recordId, $old, $new);
    }

    protected function finish(Request $request, string $message, string $redirect, array $data = []): void
    {
        Session::flash('success', $message);
        $redirectUrl = \App\Core\App::url(ltrim($redirect, '/'));
        $data['redirect'] = $data['redirect'] ?? $redirectUrl;

        // Always return JSON for AJAX so the browser never silently follows a 302
        // and leaves the user stuck on the form/detail page.
        if ($request->isAjax()) {
            $this->jsonSuccess($message, $data);
        }

        $this->redirect($redirect);
    }

    protected function requireRow(string $table, string $id, string $moduleLabel = 'Record'): array
    {
        $row = Database::fetch("SELECT * FROM {$table} WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$row) {
            $this->jsonError($moduleLabel . ' not found.', null, 404);
        }
        return $row;
    }

    protected function softDelete(Request $request, string $table, string $id, string $module): void
    {
        $old = $this->requireRow($table, $id, ucfirst(str_replace('_', ' ', $module)));
        Database::update($table, [
            'deleted_at' => $this->now(),
            'updated_at' => $this->now(),
        ], 'id = :_id', ['_id' => (int) $id]);
        $this->audit($module, 'delete', (int) $id, $old);
        $this->jsonSuccess(ucwords(str_replace('_', ' ', $module)) . ' deleted successfully.');
    }

    protected function actions(string $module, string $basePath, int|string $id, bool $view = false, string $extra = ''): string
    {
        $html = '<div class="table-actions">';
        if ($view && can($module . '.view')) {
            $html .= '<a href="' . app_url($basePath . '/' . $id) . '" class="btn btn-action" title="View"><i class="bi bi-eye"></i></a>';
        }
        if (can($module . '.edit')) {
            $html .= '<a href="' . app_url($basePath . '/' . $id . '/edit') . '" class="btn btn-action" title="Edit"><i class="bi bi-pencil"></i></a>';
        }
        if (can($module . '.delete')) {
            $html .= '<button type="button" class="btn btn-action btn-action-danger btn-delete" data-url="' . app_url($basePath . '/' . $id . '/delete') . '" title="Delete"><i class="bi bi-trash"></i></button>';
        }
        $html .= $extra;
        return $html . '</div>';
    }

    protected function activeValue(Request $request): int
    {
        return (int) ($request->input('is_active', 1) ? 1 : 0);
    }

    protected function insertWithTimestamps(string $table, array $payload): int
    {
        $payload['created_at'] = $payload['created_at'] ?? $this->now();
        $payload['updated_at'] = $payload['updated_at'] ?? $this->now();
        return Database::insert($table, $payload);
    }

    protected function updateWithTimestamp(string $table, array $payload, int $id): int
    {
        $payload['updated_at'] = $this->now();
        return Database::update($table, $payload, 'id = :_id', ['_id' => $id]);
    }

    protected function money(mixed $value): float
    {
        return round((float) ($value ?: 0), 2);
    }

    protected function currentUserId(): ?int
    {
        return Auth::id();
    }
}

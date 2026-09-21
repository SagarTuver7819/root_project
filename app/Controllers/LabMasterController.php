<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\DataTable;
use App\Core\Request;

class LabMasterController extends \App\Core\Controller
{
    use CrudControllerSupport;

    public function index(Request $request): void
    {
        self::ensureReady();
        $this->view('modules/lab-masters/index', [
            'title' => 'Lab Master',
            'pageTitle' => 'Lab Master',
        ]);
    }

    public function datatable(Request $request): void
    {
        self::ensureReady();
        DataTable::make($request, [
            'from' => 'lab_masters lm',
            'columns' => ['lm.id', 'lm.name', 'lm.is_active'],
            'searchable' => ['lm.name'],
            'orderable' => [0 => 'lm.id', 1 => 'lm.name'],
            'defaultOrder' => ['lm.id', 'DESC'],
            'where' => ['lm.deleted_at IS NULL'],
            'rowFormatter' => function (array $row) {
                $row['status_badge'] = status_badge($row['is_active'] ? 'active' : 'inactive');
                $row['actions'] = $this->actions('lab_masters', 'lab-masters', $row['id']);
                return $row;
            },
        ]);
    }

    public function create(Request $request): void
    {
        self::ensureReady();
        $this->view('modules/lab-masters/form', [
            'title' => 'Add Lab',
            'pageTitle' => 'Add Lab',
            'lab' => null,
        ]);
    }

    public function store(Request $request): void
    {
        self::ensureReady();
        $data = $this->validate($request, ['name' => 'required|max:150']);
        $payload = [
            'name' => trim((string) $data['name']),
            'is_active' => 1,
        ];
        $id = $this->insertWithTimestamps('lab_masters', $payload);
        $this->audit('lab_masters', 'create', $id, null, $payload);
        $this->finish($request, 'Lab created successfully.', 'lab-masters', ['id' => $id]);
    }

    public function edit(Request $request, string $id): void
    {
        self::ensureReady();
        $this->view('modules/lab-masters/form', [
            'title' => 'Edit Lab',
            'pageTitle' => 'Edit Lab',
            'lab' => $this->requireRow('lab_masters', $id, 'Lab'),
        ]);
    }

    public function update(Request $request, string $id): void
    {
        self::ensureReady();
        $old = $this->requireRow('lab_masters', $id, 'Lab');
        $data = $this->validate($request, ['name' => 'required|max:150']);
        $payload = [
            'name' => trim((string) $data['name']),
            'is_active' => 1,
        ];
        $this->updateWithTimestamp('lab_masters', $payload, (int) $id);
        $this->audit('lab_masters', 'update', (int) $id, $old, $payload);
        $this->finish($request, 'Lab updated successfully.', 'lab-masters');
    }

    public function destroy(Request $request, string $id): void
    {
        self::ensureReady();
        $this->softDelete($request, 'lab_masters', $id, 'lab_masters');
    }

    /**
     * Ensure lab_masters table + admin/super_admin permissions exist (safe to call often).
     */
    public static function ensureReady(): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }

        Database::connection()->exec(
            "CREATE TABLE IF NOT EXISTS lab_masters (
              id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              name VARCHAR(150) NOT NULL,
              is_active TINYINT(1) NOT NULL DEFAULT 1,
              created_at DATETIME NULL,
              updated_at DATETIME NULL,
              deleted_at DATETIME NULL,
              INDEX idx_lab_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $now = date('Y-m-d H:i:s');
        $actions = ['view', 'add', 'edit', 'delete'];
        $permissionIds = [];
        foreach ($actions as $action) {
            $slug = 'lab_masters.' . $action;
            $existing = Database::fetch('SELECT id FROM permissions WHERE slug = ?', [$slug]);
            if ($existing) {
                $permissionIds[] = (int) $existing['id'];
                continue;
            }
            $permissionIds[] = (int) Database::insert('permissions', [
                'module' => 'lab_masters',
                'action' => $action,
                'slug' => $slug,
                'name' => 'Lab Masters - ' . ucfirst($action),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (['super_admin', 'admin'] as $roleSlug) {
            $role = Database::fetch('SELECT id FROM roles WHERE slug = ?', [$roleSlug]);
            if (!$role) {
                continue;
            }
            foreach ($permissionIds as $pid) {
                Database::query(
                    'INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)',
                    [(int) $role['id'], $pid]
                );
            }
        }

        // Doctor can view labs (for dropdowns later)
        $doctor = Database::fetch('SELECT id FROM roles WHERE slug = ?', ['doctor']);
        $viewPerm = Database::fetch('SELECT id FROM permissions WHERE slug = ?', ['lab_masters.view']);
        if ($doctor && $viewPerm) {
            Database::query(
                'INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)',
                [(int) $doctor['id'], (int) $viewPerm['id']]
            );
        }

        Auth::clearPermissionCache();
        $ready = true;
    }
}

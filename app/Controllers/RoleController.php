<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;

class RoleController extends \App\Core\Controller
{
    use CrudControllerSupport;

    public function index(Request $request): void
    {
        $this->view('modules/roles/index', ['title' => 'Roles', 'pageTitle' => 'Roles', 'roles' => Database::fetchAll('SELECT r.*, COUNT(rp.permission_id) AS permission_count FROM roles r LEFT JOIN role_permissions rp ON rp.role_id = r.id GROUP BY r.id ORDER BY r.name')]);
    }

    public function edit(Request $request, string $id): void
    {
        $role = Database::fetch('SELECT * FROM roles WHERE id = ?', [$id]);
        if (!$role) {
            $this->jsonError('Role not found.', null, 404);
        }
        $this->view('modules/roles/edit', ['title' => 'Edit Role', 'pageTitle' => 'Edit Role', 'role' => $role, 'permissions' => Database::fetchAll('SELECT * FROM permissions ORDER BY module, action'), 'assigned' => array_column(Database::fetchAll('SELECT permission_id FROM role_permissions WHERE role_id = ?', [$id]), 'permission_id')]);
    }

    public function update(Request $request, string $id): void
    {
        $role = Database::fetch('SELECT * FROM roles WHERE id = ?', [$id]);
        if (!$role) {
            $this->jsonError('Role not found.', null, 404);
        }
        Database::query('DELETE FROM role_permissions WHERE role_id = ?', [$id]);
        foreach ((array) $request->input('permission_ids', []) as $permissionId) {
            if ($permissionId !== '') {
                Database::insert('role_permissions', ['role_id' => (int) $id, 'permission_id' => (int) $permissionId]);
            }
        }
        $this->audit('roles', 'permissions_update', (int) $id, null, $request->input('permission_ids', []));
        $this->finish($request, 'Role permissions updated successfully.', 'roles');
    }
}

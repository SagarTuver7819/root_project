<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\DataTable;
use App\Core\Request;

class UserController extends \App\Core\Controller
{
    use CrudControllerSupport;

    public function index(Request $request): void
    {
        $this->view('modules/users/index', ['title' => 'Users', 'pageTitle' => 'Users']);
    }

    public function datatable(Request $request): void
    {
        DataTable::make($request, [
            'from' => 'users u',
            'joins' => ['LEFT JOIN user_roles ur ON ur.user_id = u.id', 'LEFT JOIN roles r ON r.id = ur.role_id'],
            'columns' => ['u.id', 'u.name', 'u.username', 'u.email', 'u.phone', 'GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR ", ") AS roles', 'u.is_active'],
            'searchable' => ['u.name', 'u.username', 'u.email', 'u.phone', 'r.name'],
            'orderable' => [0 => 'u.id', 1 => 'u.name', 2 => 'u.username'],
            'defaultOrder' => ['u.id', 'DESC'],
            'where' => ['u.deleted_at IS NULL'],
            'groupBy' => 'u.id',
            'rowFormatter' => function (array $row) {
                $row['status_badge'] = status_badge($row['is_active'] ? 'active' : 'inactive');
                $row['actions'] = $this->actions('users', 'users', $row['id']);
                return $row;
            },
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('modules/users/form', ['title' => 'Add User', 'pageTitle' => 'Add User', 'user' => null, 'roles' => Database::fetchAll('SELECT * FROM roles ORDER BY name')]);
    }

    public function store(Request $request): void
    {
        $data = $this->validate($request, ['name' => 'required|max:150', 'username' => 'required|unique:users,username', 'email' => 'required|email|unique:users,email', 'password' => 'required|min:6']);
        $payload = $this->payload($request, $data);
        $payload['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        $id = $this->insertWithTimestamps('users', $payload);
        $this->syncRoles($id, (array) $request->input('role_ids', []));
        $this->audit('users', 'create', $id, null, $payload);
        $this->finish($request, 'User created successfully.', 'users', ['id' => $id]);
    }

    public function edit(Request $request, string $id): void
    {
        $this->view('modules/users/form', ['title' => 'Edit User', 'pageTitle' => 'Edit User', 'user' => $this->requireRow('users', $id, 'User'), 'roles' => Database::fetchAll('SELECT * FROM roles ORDER BY name'), 'assignedRoles' => array_column(Database::fetchAll('SELECT role_id FROM user_roles WHERE user_id = ?', [$id]), 'role_id')]);
    }

    public function update(Request $request, string $id): void
    {
        $old = $this->requireRow('users', $id, 'User');
        $data = $this->validate($request, ['name' => 'required|max:150', 'username' => 'required|unique:users,username,' . $id, 'email' => 'required|email|unique:users,email,' . $id]);
        $payload = $this->payload($request, $data);
        if ($request->input('password')) {
            $payload['password'] = password_hash((string) $request->input('password'), PASSWORD_BCRYPT);
        }
        $this->updateWithTimestamp('users', $payload, (int) $id);
        $this->syncRoles((int) $id, (array) $request->input('role_ids', []));
        $this->audit('users', 'update', (int) $id, $old, $payload);
        $this->finish($request, 'User updated successfully.', 'users');
    }

    public function assignRole(Request $request, string $id): void
    {
        $this->requireRow('users', $id, 'User');
        $this->syncRoles((int) $id, (array) $request->input('role_ids', []));
        $this->audit('users', 'assign_role', (int) $id, null, $request->input('role_ids', []));
        $this->finish($request, 'User roles updated successfully.', 'users/' . $id . '/edit');
    }

    public function destroy(Request $request, string $id): void
    {
        $this->softDelete($request, 'users', $id, 'users');
    }

    private function payload(Request $request, array $data): array
    {
        return ['name' => $data['name'], 'username' => $data['username'], 'email' => $data['email'], 'phone' => $request->input('phone'), 'is_active' => $this->activeValue($request)];
    }

    private function syncRoles(int $userId, array $roleIds): void
    {
        Database::query('DELETE FROM user_roles WHERE user_id = ?', [$userId]);
        foreach ($roleIds as $roleId) {
            if ($roleId !== '') {
                Database::insert('user_roles', ['user_id' => $userId, 'role_id' => (int) $roleId]);
            }
        }
    }
}

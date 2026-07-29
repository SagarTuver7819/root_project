<?php

namespace App\Controllers;

use App\Core\DataTable;
use App\Core\Request;

class AppointmentStatusController extends \App\Core\Controller
{
    use CrudControllerSupport;

    public function index(Request $request): void
    {
        $this->view('modules/appointment-statuses/index', ['title' => 'Appointment Statuses', 'pageTitle' => 'Appointment Status Master']);
    }

    public function datatable(Request $request): void
    {
        DataTable::make($request, [
            'from' => 'appointment_statuses s',
            'columns' => ['s.id', 's.name', 's.slug', 's.color', 's.badge_class', 's.sort_order', 's.is_active'],
            'searchable' => ['s.name', 's.slug', 's.badge_class'],
            'orderable' => [0 => 's.id', 1 => 's.name', 2 => 's.slug', 5 => 's.sort_order'],
            'defaultOrder' => ['s.sort_order', 'ASC'],
            'where' => ['s.deleted_at IS NULL'],
            'rowFormatter' => function (array $row) {
                $color = e($row['color'] ?? '#00AEEF');
                $row['color_badge'] = '<span class="badge" style="background-color:' . $color . ';color:#fff;">' . e($row['name']) . ' (' . $color . ')</span>';
                $row['status_badge'] = status_badge($row['is_active'] ? 'active' : 'inactive');
                $row['actions'] = $this->actions('appointment_statuses', 'appointment-statuses', $row['id']);
                return $row;
            },
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('modules/appointment-statuses/form', ['title' => 'Add Status', 'pageTitle' => 'Add Appointment Status', 'status' => null]);
    }

    public function store(Request $request): void
    {
        $data = $this->validate($request, ['name' => 'required|max:100']);
        $payload = $this->payload($request, $data);
        if (empty($payload['slug'])) {
            $payload['slug'] = strtolower(str_replace([' ', '-'], '_', trim($data['name'])));
        }
        $id = $this->insertWithTimestamps('appointment_statuses', $payload);
        $this->audit('appointment_statuses', 'create', $id, null, $payload);
        $this->finish($request, 'Status created successfully.', 'appointment-statuses', ['id' => $id]);
    }

    public function edit(Request $request, string $id): void
    {
        $this->view('modules/appointment-statuses/form', ['title' => 'Edit Status', 'pageTitle' => 'Edit Appointment Status', 'status' => $this->requireRow('appointment_statuses', $id, 'Appointment Status')]);
    }

    public function update(Request $request, string $id): void
    {
        $old = $this->requireRow('appointment_statuses', $id, 'Appointment Status');
        $data = $this->validate($request, ['name' => 'required|max:100']);
        $payload = $this->payload($request, $data);
        if (empty($payload['slug'])) {
            $payload['slug'] = $old['slug'];
        }
        $this->updateWithTimestamp('appointment_statuses', $payload, (int) $id);
        $this->audit('appointment_statuses', 'update', (int) $id, $old, $payload);
        $this->finish($request, 'Status updated successfully.', 'appointment-statuses');
    }

    public function destroy(Request $request, string $id): void
    {
        $this->softDelete($request, 'appointment_statuses', $id, 'appointment_statuses');
    }

    private function payload(Request $request, array $data): array
    {
        $badgeClass = $request->input('badge_class') ?: 'primary';
        return [
            'name' => $data['name'],
            'slug' => strtolower(str_replace([' ', '-'], '_', trim($request->input('slug') ?: $data['name']))),
            'color' => $request->input('color') ?: '#00AEEF',
            'badge_class' => $badgeClass,
            'sort_order' => (int) ($request->input('sort_order', 0)),
            'is_active' => $this->activeValue($request),
        ];
    }
}

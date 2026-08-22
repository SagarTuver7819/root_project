<?php

namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Database;
use App\Core\DataTable;
use App\Core\Request;
use App\Core\Session;

class QuotationController extends \App\Core\Controller
{
    use CrudControllerSupport;

    public function index(Request $request): void
    {
        $this->view('modules/quotations/index', ['title' => 'Quotations', 'pageTitle' => 'Quotations']);
    }

    public function datatable(Request $request): void
    {
        $this->ensureSchema();
        DataTable::make($request, [
            'from' => 'quotations q',
            'joins' => [
                'INNER JOIN patients p ON p.id = q.patient_id',
                'LEFT JOIN doctors d ON d.id = q.doctor_id',
            ],
            'columns' => [
                'q.id', 'q.quotation_number', 'q.quotation_date', 'p.name AS patient_name',
                'd.name AS doctor_name', 'q.net_amount', 'q.status',
            ],
            'searchable' => ['q.quotation_number', 'p.name', 'd.name', 'q.status'],
            'orderable' => [0 => 'q.id', 1 => 'q.quotation_number', 2 => 'q.quotation_date', 5 => 'q.net_amount'],
            'defaultOrder' => ['q.id', 'DESC'],
            'where' => ['q.deleted_at IS NULL'],
            'rowFormatter' => function (array $row) {
                $row['quotation_date'] = format_date($row['quotation_date'] ?? null);
                $row['net_amount'] = format_money($row['net_amount'] ?? 0);
                $row['doctor_name'] = doctor_label($row['doctor_name'] ?? null);
                $row['status_badge'] = status_badge($row['status'] ?? 'draft');
                $row['actions'] = $this->actions(
                    'quotations',
                    'quotations',
                    $row['id'],
                    false,
                    '<a href="' . app_url('quotations/' . $row['id'] . '/print') . '" class="btn btn-action btn-action-primary" target="_blank" title="Print"><i class="bi bi-printer"></i></a>'
                );
                return $row;
            },
        ]);
    }

    public function create(Request $request): void
    {
        $this->ensureSchema();
        $patientId = (int) $request->query('patient_id', 0);
        $fromPlan = $request->query('from_plan') === '1';

        if ($fromPlan && $patientId > 0 && can('quotations.add')) {
            $id = $this->upsertDraftFromSuggestedPlan($patientId);
            $this->redirect('quotations/' . $id . '/edit');
        }

        $this->view('modules/quotations/form', $this->formData(null, 'Create Quotation'));
    }

    public function fromSuggestedPlan(Request $request, string $patientId): void
    {
        $this->ensureSchema();
        $patient = Database::fetch('SELECT id FROM patients WHERE id = ? AND deleted_at IS NULL', [(int) $patientId]);
        if (!$patient) {
            Session::flash('error', 'Patient not found.');
            $this->redirect('patients');
        }

        if (!can('quotations.add') && !can('quotations.edit')) {
            $this->jsonError('You do not have permission to manage quotations.', null, 403);
        }

        $id = $this->upsertDraftFromSuggestedPlan((int) $patientId);
        Session::flash('success', 'Treatment estimate prepared from suggested plan.');
        $this->redirect('patients/' . $patientId . '?tab=estimate');
    }

    public function syncDraftForPatient(int $patientId): ?int
    {
        $this->ensureSchema();
        $suggested = Database::fetchAll(
            'SELECT id FROM patient_suggested_treatments WHERE patient_id = ? LIMIT 1',
            [$patientId]
        );
        if ($suggested === []) {
            return null;
        }
        return $this->upsertDraftFromSuggestedPlan($patientId);
    }

    public function ensureSchemaPublic(): void
    {
        $this->ensureSchema();
    }

    public function store(Request $request): void
    {
        $this->ensureSchema();
        $data = $this->validate($request, ['patient_id' => 'required', 'quotation_date' => 'required']);
        $items = $this->normalizeItems($request);
        if ($items === []) {
            $this->jsonError('Add at least one treatment line to the quotation.');
        }

        [$gross, $net] = $this->totals($items, $request);
        $payload = [
            'quotation_number' => $this->nextCode('quotations', 'quotation_number', 'QUOT'),
            'patient_id' => (int) $data['patient_id'],
            'doctor_id' => $request->input('doctor_id') ?: null,
            'quotation_date' => $data['quotation_date'],
            'gross_amount' => $gross,
            'discount' => $this->money($request->input('discount')),
            'net_amount' => $net,
            'status' => $request->input('status') ?: 'draft',
            'notes' => trim((string) $request->input('notes')) ?: null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ];
        $id = $this->insertWithTimestamps('quotations', $payload);
        $this->saveItems((int) $id, $items);
        $this->audit('quotations', 'create', $id, null, $payload);
        $this->finish($request, 'Quotation created successfully.', 'quotations/' . $id . '/edit', ['id' => $id]);
    }

    public function edit(Request $request, string $id): void
    {
        $this->ensureSchema();
        $this->view('modules/quotations/form', $this->formData($this->requireRow('quotations', $id, 'Quotation'), 'Edit Quotation'));
    }

    public function update(Request $request, string $id): void
    {
        $this->ensureSchema();
        $old = $this->requireRow('quotations', $id, 'Quotation');
        $data = $this->validate($request, ['patient_id' => 'required', 'quotation_date' => 'required']);
        $items = $this->normalizeItems($request);
        if ($items === []) {
            $this->jsonError('Add at least one treatment line to the quotation.');
        }

        [$gross, $net] = $this->totals($items, $request);
        $payload = [
            'patient_id' => (int) $data['patient_id'],
            'doctor_id' => $request->input('doctor_id') ?: null,
            'quotation_date' => $data['quotation_date'],
            'gross_amount' => $gross,
            'discount' => $this->money($request->input('discount')),
            'net_amount' => $net,
            'status' => $request->input('status') ?: ($old['status'] ?? 'draft'),
            'notes' => trim((string) $request->input('notes')) ?: null,
            'updated_by' => Auth::id(),
        ];
        $this->updateWithTimestamp('quotations', $payload, (int) $id);
        $this->saveItems((int) $id, $items);
        $this->audit('quotations', 'update', (int) $id, $old, $payload);
        $this->finish($request, 'Quotation updated successfully.', 'quotations/' . $id . '/edit');
    }

    public function destroy(Request $request, string $id): void
    {
        $this->ensureSchema();
        $this->softDelete($request, 'quotations', $id, 'quotations');
    }

    public function print(Request $request, string $id): void
    {
        $this->ensureSchema();
        $quotation = Database::fetch(
            'SELECT q.*, p.name AS patient_name, p.patient_code, p.mobile, p.email, p.address, p.age, p.gender,
                    d.name AS doctor_name, d.qualification
             FROM quotations q
             INNER JOIN patients p ON p.id = q.patient_id
             LEFT JOIN doctors d ON d.id = q.doctor_id
             WHERE q.id = ? AND q.deleted_at IS NULL',
            [$id]
        );
        if (!$quotation) {
            $this->jsonError('Quotation not found.', null, 404);
        }

        $items = Database::fetchAll(
            'SELECT qi.*, d.name AS doctor_name
             FROM quotation_items qi
             LEFT JOIN doctors d ON d.id = qi.doctor_id
             WHERE qi.quotation_id = ?
             ORDER BY qi.sort_order ASC, qi.id ASC',
            [$id]
        );

        $this->view('modules/quotations/print', [
            'title' => 'Quotation ' . ($quotation['quotation_number'] ?? ''),
            'quotation' => $quotation,
            'items' => $items,
            'autoPrint' => $request->query('print') === '1',
        ], null);
    }

    private function upsertDraftFromSuggestedPlan(int $patientId): int
    {
        $suggested = Database::fetchAll(
            'SELECT id, description, doctor_id, teeth, sort_order
             FROM patient_suggested_treatments
             WHERE patient_id = ?
             ORDER BY sort_order ASC, id ASC',
            [$patientId]
        );
        if ($suggested === []) {
            Session::flash('error', 'Save at least one treatment line in the suggested plan first.');
            $this->redirect('patients/' . $patientId . '?tab=plan');
        }

        $existing = Database::fetch(
            "SELECT id FROM quotations WHERE patient_id = ? AND status = 'draft' AND deleted_at IS NULL ORDER BY id DESC LIMIT 1",
            [$patientId]
        );

        $items = $this->itemsFromSuggested($suggested);
        [$gross, $net] = $this->totalsFromItems($items, 0);
        $doctorId = null;
        foreach ($suggested as $row) {
            if (!empty($row['doctor_id'])) {
                $doctorId = (int) $row['doctor_id'];
                break;
            }
        }

        $now = $this->now();
        if ($existing) {
            $id = (int) $existing['id'];
            Database::update('quotations', [
                'doctor_id' => $doctorId,
                'gross_amount' => $gross,
                'net_amount' => $net,
                'updated_by' => Auth::id(),
                'updated_at' => $now,
            ], 'id = :_id', ['_id' => $id]);
            $this->saveItems($id, $items);
            return $id;
        }

        $id = Database::insert('quotations', [
            'quotation_number' => $this->nextCode('quotations', 'quotation_number', 'QUOT'),
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'quotation_date' => date('Y-m-d'),
            'gross_amount' => $gross,
            'discount' => 0,
            'net_amount' => $net,
            'status' => 'draft',
            'notes' => 'Prepared from suggested treatment plan',
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->saveItems($id, $items);
        return $id;
    }

    /** @param list<array<string,mixed>> $suggested */
    private function itemsFromSuggested(array $suggested): array
    {
        $masters = Database::fetchAll(
            'SELECT id, name, default_price FROM treatment_masters WHERE deleted_at IS NULL AND is_active = 1'
        );
        $items = [];
        foreach ($suggested as $row) {
            $desc = trim((string) ($row['description'] ?? ''));
            if ($desc === '') {
                continue;
            }
            $price = $this->matchTreatmentPrice($desc, $masters);
            $items[] = [
                'description' => $desc,
                'teeth' => trim((string) ($row['teeth'] ?? '')),
                'doctor_id' => (int) ($row['doctor_id'] ?? 0) ?: null,
                'unit_price' => $price,
                'amount' => $price,
                'suggested_treatment_id' => (int) ($row['id'] ?? 0) ?: null,
            ];
        }
        return $items;
    }

    /** @param list<array<string,mixed>> $masters */
    private function matchTreatmentPrice(string $description, array $masters): float
    {
        $needle = strtolower(trim($description));
        if ($needle === '') {
            return 0.0;
        }

        foreach ($masters as $master) {
            $name = strtolower(trim((string) ($master['name'] ?? '')));
            if ($name !== '' && $name === $needle) {
                return $this->money($master['default_price'] ?? 0);
            }
        }
        foreach ($masters as $master) {
            $name = strtolower(trim((string) ($master['name'] ?? '')));
            if ($name !== '' && (str_contains($needle, $name) || str_contains($name, $needle))) {
                return $this->money($master['default_price'] ?? 0);
            }
        }
        return 0.0;
    }

    private function normalizeItems(Request $request): array
    {
        $raw = $request->input('items', []);
        if (!is_array($raw)) {
            return [];
        }
        $items = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $desc = trim((string) ($row['description'] ?? ''));
            if ($desc === '') {
                continue;
            }
            $unit = $this->money($row['unit_price'] ?? 0);
            $items[] = [
                'description' => $desc,
                'teeth' => trim((string) ($row['teeth'] ?? '')),
                'doctor_id' => (int) ($row['doctor_id'] ?? 0) ?: null,
                'unit_price' => $unit,
                'amount' => $unit,
                'suggested_treatment_id' => (int) ($row['suggested_treatment_id'] ?? 0) ?: null,
            ];
        }
        return $items;
    }

    /** @param list<array<string,mixed>> $items */
    private function totals(array $items, Request $request): array
    {
        return $this->totalsFromItems($items, $this->money($request->input('discount')));
    }

    /** @param list<array<string,mixed>> $items */
    private function totalsFromItems(array $items, float $discount): array
    {
        $gross = 0.0;
        foreach ($items as $item) {
            $gross += $this->money($item['amount'] ?? $item['unit_price'] ?? 0);
        }
        $gross = $this->money($gross);
        $net = max(0, $this->money($gross - $discount));
        return [$gross, $net];
    }

    /** @param list<array<string,mixed>> $items */
    private function saveItems(int $quotationId, array $items): void
    {
        Database::query('DELETE FROM quotation_items WHERE quotation_id = ?', [$quotationId]);
        $sort = 1;
        $now = $this->now();
        foreach ($items as $item) {
            Database::insert('quotation_items', [
                'quotation_id' => $quotationId,
                'sort_order' => $sort,
                'description' => $item['description'],
                'teeth' => ($item['teeth'] ?? '') !== '' ? $item['teeth'] : null,
                'doctor_id' => $item['doctor_id'] ?? null,
                'unit_price' => $this->money($item['unit_price'] ?? 0),
                'amount' => $this->money($item['amount'] ?? $item['unit_price'] ?? 0),
                'suggested_treatment_id' => $item['suggested_treatment_id'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $sort++;
        }
    }

    private function formData(?array $quotation, string $title): array
    {
        $patientId = (int) ($quotation['patient_id'] ?? $_GET['patient_id'] ?? 0);
        $items = [];
        if (!empty($quotation['id'])) {
            $items = Database::fetchAll(
                'SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY sort_order ASC, id ASC',
                [(int) $quotation['id']]
            );
        } elseif ($patientId > 0) {
            $suggested = Database::fetchAll(
                'SELECT id, description, doctor_id, teeth, sort_order
                 FROM patient_suggested_treatments WHERE patient_id = ? ORDER BY sort_order ASC, id ASC',
                [$patientId]
            );
            if ($suggested !== []) {
                $items = $this->itemsFromSuggested($suggested);
            }
        }

        if ($items === []) {
            $items = [['description' => '', 'teeth' => '', 'doctor_id' => '', 'unit_price' => 0, 'amount' => 0]];
        }

        return [
            'title' => $title,
            'pageTitle' => $title,
            'quotation' => $quotation,
            'items' => $items,
            'patients' => $this->options('patients', 'name', 'is_active = 1'),
            'doctors' => $this->options('doctors', 'name', 'is_active = 1'),
            'treatments' => Database::fetchAll(
                'SELECT id, name, default_price FROM treatment_masters WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name ASC'
            ),
        ];
    }

    private function ensureSchema(): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }

        Database::connection()->exec(
            "CREATE TABLE IF NOT EXISTS quotations (
              id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              quotation_number VARCHAR(50) NOT NULL UNIQUE,
              patient_id INT UNSIGNED NOT NULL,
              doctor_id INT UNSIGNED NULL,
              quotation_date DATE NOT NULL,
              gross_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
              discount DECIMAL(12,2) NOT NULL DEFAULT 0,
              net_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
              status VARCHAR(30) NOT NULL DEFAULT 'draft',
              notes TEXT NULL,
              created_by INT UNSIGNED NULL,
              updated_by INT UNSIGNED NULL,
              created_at DATETIME NULL,
              updated_at DATETIME NULL,
              deleted_at DATETIME NULL,
              INDEX idx_quot_patient (patient_id),
              INDEX idx_quot_date (quotation_date),
              INDEX idx_quot_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        Database::connection()->exec(
            "CREATE TABLE IF NOT EXISTS quotation_items (
              id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              quotation_id INT UNSIGNED NOT NULL,
              sort_order INT UNSIGNED NOT NULL DEFAULT 1,
              description VARCHAR(255) NOT NULL,
              teeth VARCHAR(255) NULL,
              doctor_id INT UNSIGNED NULL,
              unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
              amount DECIMAL(12,2) NOT NULL DEFAULT 0,
              suggested_treatment_id INT UNSIGNED NULL,
              created_at DATETIME NULL,
              updated_at DATETIME NULL,
              INDEX idx_qi_quotation (quotation_id),
              FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $ready = true;
    }
}

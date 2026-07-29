<?php

namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DataTable;
use App\Core\Request;
use App\Core\Session;
use App\Services\AuditService;

class PatientController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('modules/patients/index', [
            'title' => 'Patients',
            'pageTitle' => 'Patient List',
        ]);
    }

    public function history(Request $request): void
    {
        $q = trim((string) ($request->query('q') ?? $request->query('mobile') ?? ''));
        $dateFrom = trim((string) ($request->query('date_from') ?? ''));
        $dateTo = trim((string) ($request->query('date_to') ?? ''));
        if ($dateFrom !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $dateFrom = '';
        }
        if ($dateTo !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $dateTo = '';
        }

        $patient = null;
        $matches = [];
        $timeline = [];
        $patients = [];
        $summary = [
            'appointments' => 0,
            'visits' => 0,
            'treatments' => 0,
            'prescriptions' => 0,
            'payments' => 0,
        ];

        if ($q !== '') {
            $digits = preg_replace('/\D+/', '', $q) ?? '';
            $like = '%' . $q . '%';
            $params = [$like, $like, $like];
            $sql = 'SELECT id, patient_code, name, mobile, gender, age, registration_date, is_active, allergies, medical_history
                    FROM patients
                    WHERE deleted_at IS NULL
                      AND (name LIKE ? OR mobile LIKE ? OR patient_code LIKE ?';
            if ($digits !== '' && strlen($digits) >= 4) {
                $sql .= ' OR REPLACE(REPLACE(REPLACE(mobile, " ", ""), "-", ""), "+", "") LIKE ?';
                $params[] = '%' . $digits . '%';
            }
            $sql .= ') ORDER BY name ASC LIMIT 25';
            $matches = Database::fetchAll($sql, $params);

            if (count($matches) === 1) {
                $patient = $matches[0];
            } elseif (count($matches) > 1 && $digits !== '') {
                foreach ($matches as $row) {
                    $m = preg_replace('/\D+/', '', (string) ($row['mobile'] ?? '')) ?? '';
                    if ($m === $digits || str_ends_with($m, $digits)) {
                        $patient = $row;
                        break;
                    }
                }
            }

            if ($patient) {
                $pid = (int) $patient['id'];
                $timeline = $this->timeline($pid, 200, 0, $dateFrom ?: null, $dateTo ?: null);
                $summary = $this->patientHistorySummary($pid, $dateFrom ?: null, $dateTo ?: null);
            }
        } else {
            // Default: All patients basic history overview (optionally filtered by activity dates).
            $patients = $this->allPatientsHistoryOverview($dateFrom ?: null, $dateTo ?: null);
        }

        $this->view('modules/patients/history', [
            'title' => 'Patient History',
            'pageTitle' => 'Patient History',
            'q' => $q,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'patient' => $patient,
            'matches' => $matches,
            'patients' => $patients,
            'timeline' => $timeline,
            'summary' => $summary,
        ]);
    }

    private function allPatientsHistoryOverview(?string $dateFrom, ?string $dateTo): array
    {
        $aptWhere = ["a.deleted_at IS NULL", "IFNULL(a.entry_type,'appointment') <> 'doctor_remark'"];
        $visitWhere = ['v.deleted_at IS NULL'];
        $treatWhere = ['t.deleted_at IS NULL'];
        $apParams = [];
        $viParams = [];
        $trParams = [];

        if ($dateFrom) {
            $aptWhere[] = 'a.appointment_date >= ?';
            $visitWhere[] = 'v.visit_date >= ?';
            $treatWhere[] = 'COALESCE(t.start_date, DATE(t.created_at)) >= ?';
            $apParams[] = $dateFrom;
            $viParams[] = $dateFrom;
            $trParams[] = $dateFrom;
        }
        if ($dateTo) {
            $aptWhere[] = 'a.appointment_date <= ?';
            $visitWhere[] = 'v.visit_date <= ?';
            $treatWhere[] = 'COALESCE(t.start_date, DATE(t.created_at)) <= ?';
            $apParams[] = $dateTo;
            $viParams[] = $dateTo;
            $trParams[] = $dateTo;
        }

        $aptSql = implode(' AND ', $aptWhere);
        $visitSql = implode(' AND ', $visitWhere);
        $treatSql = implode(' AND ', $treatWhere);
        $havingActivity = ($dateFrom || $dateTo)
            ? ' HAVING (appointments_count + visits_count + treatments_count) > 0'
            : '';

        $sql = "SELECT p.id, p.patient_code, p.name, p.mobile, p.gender, p.age, p.registration_date, p.is_active,
                       COALESCE(ap.c, 0) AS appointments_count,
                       COALESCE(vi.c, 0) AS visits_count,
                       COALESCE(tr.c, 0) AS treatments_count,
                       GREATEST(
                           COALESCE(ap.last_date, '1970-01-01'),
                           COALESCE(vi.last_date, '1970-01-01'),
                           COALESCE(tr.last_date, '1970-01-01'),
                           COALESCE(p.registration_date, '1970-01-01')
                       ) AS last_activity_date
                FROM patients p
                LEFT JOIN (
                    SELECT a.patient_id, COUNT(*) c, MAX(a.appointment_date) last_date
                    FROM appointments a
                    WHERE {$aptSql}
                    GROUP BY a.patient_id
                ) ap ON ap.patient_id = p.id
                LEFT JOIN (
                    SELECT v.patient_id, COUNT(*) c, MAX(v.visit_date) last_date
                    FROM patient_visits v
                    WHERE {$visitSql}
                    GROUP BY v.patient_id
                ) vi ON vi.patient_id = p.id
                LEFT JOIN (
                    SELECT t.patient_id, COUNT(*) c, MAX(COALESCE(t.start_date, DATE(t.created_at))) last_date
                    FROM patient_treatment_plans t
                    WHERE {$treatSql}
                    GROUP BY t.patient_id
                ) tr ON tr.patient_id = p.id
                WHERE p.deleted_at IS NULL
                {$havingActivity}
                ORDER BY last_activity_date DESC, p.name ASC
                LIMIT 200";

        return Database::fetchAll($sql, array_merge($apParams, $viParams, $trParams));
    }

    private function patientHistorySummary(int $patientId, ?string $dateFrom, ?string $dateTo): array
    {
        $ranges = [
            'appointments' => ["SELECT COUNT(*) c FROM appointments WHERE patient_id = ? AND deleted_at IS NULL AND IFNULL(entry_type,'appointment') <> 'doctor_remark'", 'appointment_date'],
            'visits' => ['SELECT COUNT(*) c FROM patient_visits WHERE patient_id = ? AND deleted_at IS NULL', 'visit_date'],
            'treatments' => ['SELECT COUNT(*) c FROM patient_treatment_plans WHERE patient_id = ? AND deleted_at IS NULL', 'COALESCE(start_date, DATE(created_at))'],
            'prescriptions' => ['SELECT COUNT(*) c FROM prescriptions WHERE patient_id = ? AND deleted_at IS NULL', 'prescription_date'],
            'payments' => ['SELECT COUNT(*) c FROM payments WHERE patient_id = ? AND deleted_at IS NULL', 'payment_date'],
        ];

        $out = [];
        foreach ($ranges as $key => [$baseSql, $dateCol]) {
            $sql = $baseSql;
            $params = [$patientId];
            if ($dateFrom) {
                $sql .= " AND {$dateCol} >= ?";
                $params[] = $dateFrom;
            }
            if ($dateTo) {
                $sql .= " AND {$dateCol} <= ?";
                $params[] = $dateTo;
            }
            $out[$key] = (int) (Database::fetch($sql, $params)['c'] ?? 0);
        }
        return $out;
    }

    public function datatable(Request $request): void
    {
        DataTable::make($request, [
            'from' => 'patients p',
            'joins' => ['LEFT JOIN reference_doctors rd ON rd.id = p.reference_doctor_id'],
            'columns' => [
                'p.id',
                'p.patient_code',
                'p.name',
                'p.mobile',
                'p.gender',
                'p.age',
                'p.registration_date',
                'rd.name AS reference_doctor',
                'p.is_active',
            ],
            'searchable' => ['p.patient_code', 'p.name', 'p.mobile', 'p.email'],
            'orderable' => [
                0 => 'p.id',
                1 => 'p.patient_code',
                2 => 'p.name',
                3 => 'p.mobile',
                6 => 'p.registration_date',
            ],
            'defaultOrder' => ['p.id', 'DESC'],
            'where' => ['p.deleted_at IS NULL'],
            'filters' => function (Request $req, array &$where, array &$bindings) {
                $status = $req->input('status');
                if ($status === '1' || $status === '0') {
                    $where[] = 'p.is_active = ?';
                    $bindings[] = (int) $status;
                }
                $from = $req->input('date_from');
                $to = $req->input('date_to');
                if ($from) {
                    $where[] = 'p.registration_date >= ?';
                    $bindings[] = $from;
                }
                if ($to) {
                    $where[] = 'p.registration_date <= ?';
                    $bindings[] = $to;
                }
                $q = trim((string) $req->input('q', ''));
                if ($q !== '') {
                    $where[] = '(p.name LIKE ? OR p.mobile LIKE ? OR p.patient_code LIKE ? OR p.email LIKE ?)';
                    $like = '%' . $q . '%';
                    array_push($bindings, $like, $like, $like, $like);
                }
            },
            'rowFormatter' => function (array $row) {
                $actions = '<div class="table-actions">';
                if (can('patients.view')) {
                    $actions .= '<a href="' . app_url('patients/' . $row['id']) . '" class="btn btn-action" title="View"><i class="bi bi-eye"></i></a>';
                }
                if (can('patients.edit')) {
                    $actions .= '<a href="' . app_url('patients/' . $row['id'] . '/edit') . '" class="btn btn-action" title="Edit"><i class="bi bi-pencil"></i></a>';
                }
                if (can('patients.delete')) {
                    $actions .= '<button type="button" class="btn btn-action btn-action-danger btn-delete" data-url="' . app_url('patients/' . $row['id'] . '/delete') . '" title="Delete"><i class="bi bi-trash"></i></button>';
                }
                $actions .= '</div>';
                $row['registration_date'] = format_date($row['registration_date'] ?? null);
                $row['status_badge'] = status_badge($row['is_active'] ? 'active' : 'inactive');
                $row['actions'] = $actions;
                return $row;
            },
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('modules/patients/form', [
            'title' => 'Add Patient',
            'pageTitle' => 'Add Patient',
            'patient' => null,
            'referenceDoctors' => Database::fetchAll('SELECT id, name FROM reference_doctors WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name'),
        ]);
    }

    public function store(Request $request): void
    {
        $data = $this->validate($request, [
            'name' => 'required|max:150',
            'mobile' => 'required|max:20',
            'gender' => 'in:male,female,other',
        ]);

        $code = $this->nextPatientCode();
        $id = Database::insert('patients', [
            'patient_code' => $code,
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'alternate_mobile' => $request->input('alternate_mobile'),
            'dob' => $request->input('dob') ?: null,
            'age' => $request->input('age') ?: $this->ageFromDob($request->input('dob')),
            'gender' => $request->input('gender') ?: null,
            'email' => $request->input('email'),
            'address' => $request->input('address'),
            'city' => $request->input('city'),
            'state' => $request->input('state'),
            'pincode' => $request->input('pincode'),
            'blood_group' => $request->input('blood_group'),
            'emergency_contact' => $request->input('emergency_contact'),
            'reference_doctor_id' => $request->input('reference_doctor_id') ?: null,
            'registration_date' => $request->input('registration_date') ?: date('Y-m-d'),
            'medical_history' => $request->input('medical_history'),
            'allergies' => $request->input('allergies'),
            'existing_conditions' => $request->input('existing_conditions'),
            'current_medicines' => $request->input('current_medicines'),
            'notes' => $request->input('notes'),
            'is_active' => 1,
            'created_by' => Auth::id(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        AuditService::log('patients', 'create', $id, null, ['patient_code' => $code, 'name' => $data['name']]);

        $action = $request->input('submit_action');
        $redirect = 'patients/' . $id;
        if ($action === 'save_new') {
            $redirect = 'patients/create';
        } elseif ($action === 'book') {
            $redirect = 'calendar?patient_id=' . $id;
        }

        if ($request->isAjax()) {
            $this->jsonSuccess('Patient created successfully.', [
                'id' => $id,
                'patient_code' => $code,
                'name' => $data['name'],
                'mobile' => $data['mobile'],
                'text' => $code . ' - ' . $data['name'] . ' (' . $data['mobile'] . ')',
                'redirect' => App::url($redirect),
            ]);
        }

        Session::flash('success', 'Patient created successfully.');
        $this->redirect($redirect);
    }

    public function show(Request $request, string $id): void
    {
        $patient = Database::fetch('SELECT p.*, rd.name AS reference_doctor_name FROM patients p LEFT JOIN reference_doctors rd ON rd.id = p.reference_doctor_id WHERE p.id = ? AND p.deleted_at IS NULL', [$id]);
        if (!$patient) {
            Session::flash('error', 'Patient not found.');
            $this->redirect('patients');
        }

        $this->view('modules/patients/show', [
            'title' => $patient['name'],
            'pageTitle' => 'Patient Profile',
            'patient' => $patient,
        ]);
    }

    public function edit(Request $request, string $id): void
    {
        $patient = Database::fetch('SELECT * FROM patients WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$patient) {
            Session::flash('error', 'Patient not found.');
            $this->redirect('patients');
        }

        $this->view('modules/patients/form', [
            'title' => 'Edit Patient',
            'pageTitle' => 'Edit Patient',
            'patient' => $patient,
            'referenceDoctors' => Database::fetchAll('SELECT id, name FROM reference_doctors WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name'),
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $patient = Database::fetch('SELECT * FROM patients WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$patient) {
            $this->jsonError('Patient not found.', null, 404);
        }

        $data = $this->validate($request, [
            'name' => 'required|max:150',
            'mobile' => 'required|max:20',
        ]);

        $payload = [
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'alternate_mobile' => $request->input('alternate_mobile'),
            'dob' => $request->input('dob') ?: null,
            'age' => $request->input('age') ?: $this->ageFromDob($request->input('dob')),
            'gender' => $request->input('gender') ?: null,
            'email' => $request->input('email'),
            'address' => $request->input('address'),
            'city' => $request->input('city'),
            'state' => $request->input('state'),
            'pincode' => $request->input('pincode'),
            'blood_group' => $request->input('blood_group'),
            'emergency_contact' => $request->input('emergency_contact'),
            'reference_doctor_id' => $request->input('reference_doctor_id') ?: null,
            'medical_history' => $request->input('medical_history'),
            'allergies' => $request->input('allergies'),
            'existing_conditions' => $request->input('existing_conditions'),
            'current_medicines' => $request->input('current_medicines'),
            'notes' => $request->input('notes'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        Database::update('patients', $payload, 'id = :_id', ['_id' => (int) $id]);
        AuditService::log('patients', 'update', (int) $id, $patient, $payload);

        Session::flash('success', 'Patient updated successfully.');
        if ($request->isAjax()) {
            $this->jsonSuccess('Patient updated successfully.', ['redirect' => App::url('patients/' . $id)]);
        }
        $this->redirect('patients/' . $id);
    }

    public function destroy(Request $request, string $id): void
    {
        $patient = Database::fetch('SELECT * FROM patients WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$patient) {
            $this->jsonError('Patient not found.', null, 404);
        }

        Database::update('patients', [
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :_id', ['_id' => (int) $id]);

        AuditService::log('patients', 'delete', (int) $id, $patient, null);
        $this->jsonSuccess('Patient deleted successfully.');
    }

    public function search(Request $request): void
    {
        $q = trim((string) $request->input('q', $request->input('term', '')));
        $params = [];
        $sql = 'SELECT id, patient_code, name, mobile FROM patients WHERE deleted_at IS NULL';
        if ($q !== '') {
            $sql .= ' AND (name LIKE ? OR mobile LIKE ? OR patient_code LIKE ?)';
            $like = '%' . $q . '%';
            $params = [$like, $like, $like];
        }
        $sql .= ' ORDER BY name ASC LIMIT 20';
        $rows = Database::fetchAll($sql, $params);

        $results = array_map(fn ($r) => [
            'id' => $r['id'],
            'text' => $r['patient_code'] . ' - ' . $r['name'] . ' (' . $r['mobile'] . ')',
            'patient_code' => $r['patient_code'],
            'name' => $r['name'],
            'mobile' => $r['mobile'],
        ], $rows);

        $this->jsonSuccess('OK', ['results' => $results]);
    }

    public function tab(Request $request, string $id, string $tab): void
    {
        $allowed = ['appointments', 'visits', 'treatments', 'prescriptions', 'payments', 'documents', 'history'];
        if (!in_array($tab, $allowed, true)) {
            $this->jsonError('Invalid tab.', null, 404);
        }

        $page = max(1, (int) $request->input('page', 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $html = '';
        $limitSql = ' LIMIT ' . $perPage . ' OFFSET ' . $offset;

        switch ($tab) {
            case 'appointments':
                $rows = Database::fetchAll(
                    'SELECT a.*, d.name AS doctor_name, tm.name AS treatment_name FROM appointments a
                     INNER JOIN doctors d ON d.id = a.doctor_id
                     LEFT JOIN treatment_masters tm ON tm.id = a.treatment_master_id
                     WHERE a.patient_id = ? AND a.deleted_at IS NULL
                     ORDER BY a.appointment_date DESC, a.start_time DESC' . $limitSql,
                    [(int) $id]
                );
                ob_start();
                require dirname(__DIR__, 2) . '/resources/views/modules/patients/tabs/appointments.php';
                $html = ob_get_clean();
                break;
            case 'visits':
                $rows = Database::fetchAll(
                    'SELECT v.*, d.name AS doctor_name FROM patient_visits v
                     INNER JOIN doctors d ON d.id = v.doctor_id
                     WHERE v.patient_id = ? AND v.deleted_at IS NULL
                     ORDER BY v.visit_date DESC' . $limitSql,
                    [(int) $id]
                );
                ob_start();
                require dirname(__DIR__, 2) . '/resources/views/modules/patients/tabs/visits.php';
                $html = ob_get_clean();
                break;
            case 'treatments':
                $rows = Database::fetchAll(
                    'SELECT t.*, tm.name AS treatment_name, d.name AS doctor_name
                     FROM patient_treatment_plans t
                     INNER JOIN treatment_masters tm ON tm.id = t.treatment_master_id
                     INNER JOIN doctors d ON d.id = t.doctor_id
                     WHERE t.patient_id = ? AND t.deleted_at IS NULL
                     ORDER BY t.id DESC' . $limitSql,
                    [(int) $id]
                );
                ob_start();
                require dirname(__DIR__, 2) . '/resources/views/modules/patients/tabs/treatments.php';
                $html = ob_get_clean();
                break;
            case 'prescriptions':
                $rows = Database::fetchAll(
                    'SELECT pr.*, d.name AS doctor_name FROM prescriptions pr
                     INNER JOIN doctors d ON d.id = pr.doctor_id
                     WHERE pr.patient_id = ? AND pr.deleted_at IS NULL
                     ORDER BY pr.prescription_date DESC' . $limitSql,
                    [(int) $id]
                );
                ob_start();
                require dirname(__DIR__, 2) . '/resources/views/modules/patients/tabs/prescriptions.php';
                $html = ob_get_clean();
                break;
            case 'payments':
                $rows = Database::fetchAll(
                    'SELECT py.*, b.bill_number FROM payments py
                     INNER JOIN bills b ON b.id = py.bill_id
                     WHERE py.patient_id = ? AND py.deleted_at IS NULL
                     ORDER BY py.payment_date DESC' . $limitSql,
                    [(int) $id]
                );
                ob_start();
                require dirname(__DIR__, 2) . '/resources/views/modules/patients/tabs/payments.php';
                $html = ob_get_clean();
                break;
            case 'documents':
                $rows = Database::fetchAll(
                    'SELECT * FROM patient_documents WHERE patient_id = ? ORDER BY id DESC' . $limitSql,
                    [(int) $id]
                );
                $id = (int) $id;
                ob_start();
                require dirname(__DIR__, 2) . '/resources/views/modules/patients/tabs/documents.php';
                $html = ob_get_clean();
                break;
            case 'history':
                $rows = $this->timeline((int) $id, 50, 0);
                ob_start();
                require dirname(__DIR__, 2) . '/resources/views/modules/patients/tabs/history.php';
                $html = ob_get_clean();
                break;
        }

        $this->jsonSuccess('OK', ['html' => $html, 'page' => $page]);
    }

    public function uploadDocument(Request $request, string $id): void
    {
        $patient = Database::fetch('SELECT id FROM patients WHERE id = ? AND deleted_at IS NULL', [$id]);
        if (!$patient) {
            $this->jsonError('Patient not found.', null, 404);
        }

        $data = $this->validate($request, ['document_type' => 'required|max:50']);
        $file = $request->file('document');
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->jsonError('Please choose a document file to upload.');
        }

        $path = $this->storePatientDocument($file, (int) $id);
        if (!$path) {
            $this->jsonError('Unsupported file type. Allowed: PDF, JPG, PNG, WEBP.');
        }

        $docId = Database::insert('patient_documents', [
            'patient_id' => (int) $id,
            'document_type' => $data['document_type'],
            'file_path' => $path,
            'description' => $request->input('description'),
            'uploaded_by' => Auth::id(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        AuditService::log('patients', 'document_upload', $docId, null, ['patient_id' => (int) $id, 'path' => $path]);
        $this->jsonSuccess('Document uploaded successfully.', ['id' => $docId]);
    }

    public function deleteDocument(Request $request, string $id, string $docId): void
    {
        $doc = Database::fetch('SELECT * FROM patient_documents WHERE id = ? AND patient_id = ?', [$docId, $id]);
        if (!$doc) {
            $this->jsonError('Document not found.', null, 404);
        }

        Database::query('DELETE FROM patient_documents WHERE id = ?', [$docId]);
        $full = dirname(__DIR__, 2) . '/public/assets/uploads/' . ltrim($doc['file_path'], '/');
        if (is_file($full)) {
            @unlink($full);
        }

        AuditService::log('patients', 'document_delete', (int) $docId, $doc, null);
        $this->jsonSuccess('Document deleted successfully.');
    }

    private function storePatientDocument(array $file, int $patientId): ?string
    {
        $allowed = ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed, true)) {
            return null;
        }

        $ext = match ($mime) {
            'application/pdf' => 'pdf',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $dir = dirname(__DIR__, 2) . '/public/assets/uploads/patients/' . $patientId;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $name = 'doc_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            return null;
        }

        return 'patients/' . $patientId . '/' . $name;
    }

    private function timeline(int $patientId, int $limit, int $offset, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $dateFilter = static function (string $col) use ($dateFrom, $dateTo): array {
            $sql = '';
            $params = [];
            if ($dateFrom) {
                $sql .= " AND {$col} >= ?";
                $params[] = $dateFrom;
            }
            if ($dateTo) {
                $sql .= " AND {$col} <= ?";
                $params[] = $dateTo;
            }
            return [$sql, $params];
        };

        [$regF, $regP] = $dateFilter('registration_date');
        [$aptF, $aptP] = $dateFilter('a.appointment_date');
        [$visF, $visP] = $dateFilter('v.visit_date');
        [$trtF, $trtP] = $dateFilter('COALESCE(t.start_date, DATE(t.created_at))');
        [$preF, $preP] = $dateFilter('pr.prescription_date');
        [$payF, $payP] = $dateFilter('py.payment_date');
        [$folF, $folP] = $dateFilter('f.follow_up_date');

        $sql = "
            (SELECT 'registration' AS event_type,
                    registration_date AS event_date,
                    CONCAT('Patient registered (', patient_code, ')') AS title,
                    id AS ref_id,
                    'patients' AS module_key,
                    '' AS doctor_name,
                    '' AS status_text
             FROM patients WHERE id = ? {$regF})
            UNION ALL
            (SELECT 'appointment',
                    a.appointment_date,
                    CONCAT(
                        'Appointment ', a.appointment_code,
                        IF(tm.name IS NOT NULL AND tm.name <> '', CONCAT(' · ', tm.name), ''),
                        ' · ', REPLACE(a.status, '_', ' ')
                    ),
                    a.id,
                    'appointments',
                    IFNULL(d.name, ''),
                    a.status
             FROM appointments a
             LEFT JOIN doctors d ON d.id = a.doctor_id
             LEFT JOIN treatment_masters tm ON tm.id = a.treatment_master_id
             WHERE a.patient_id = ? AND a.deleted_at IS NULL
               AND IFNULL(a.entry_type, 'appointment') <> 'doctor_remark' {$aptF})
            UNION ALL
            (SELECT 'visit',
                    v.visit_date,
                    CONCAT(
                        'Visit ', v.visit_code,
                        IF(v.diagnosis IS NOT NULL AND v.diagnosis <> '', CONCAT(' · ', v.diagnosis), ''),
                        ' · ', REPLACE(IFNULL(v.status, ''), '_', ' ')
                    ),
                    v.id,
                    'visits',
                    IFNULL(d.name, ''),
                    IFNULL(v.status, '')
             FROM patient_visits v
             LEFT JOIN doctors d ON d.id = v.doctor_id
             WHERE v.patient_id = ? AND v.deleted_at IS NULL {$visF})
            UNION ALL
            (SELECT 'treatment',
                    COALESCE(t.start_date, DATE(t.created_at)),
                    CONCAT(
                        'Treatment ', t.plan_code, ' · ', IFNULL(tm.name, 'Plan'),
                        IF(t.tooth_number IS NOT NULL AND t.tooth_number <> '', CONCAT(' · Tooth ', t.tooth_number), ''),
                        ' · ', REPLACE(t.status, '_', ' ')
                    ),
                    t.id,
                    'treatment-plans',
                    IFNULL(d.name, ''),
                    t.status
             FROM patient_treatment_plans t
             LEFT JOIN treatment_masters tm ON tm.id = t.treatment_master_id
             LEFT JOIN doctors d ON d.id = t.doctor_id
             WHERE t.patient_id = ? AND t.deleted_at IS NULL {$trtF})
            UNION ALL
            (SELECT 'prescription',
                    pr.prescription_date,
                    CONCAT('Prescription ', pr.prescription_number),
                    pr.id,
                    'prescriptions',
                    IFNULL(d.name, ''),
                    ''
             FROM prescriptions pr
             LEFT JOIN doctors d ON d.id = pr.doctor_id
             WHERE pr.patient_id = ? AND pr.deleted_at IS NULL {$preF})
            UNION ALL
            (SELECT 'payment',
                    py.payment_date,
                    CONCAT('Payment ', py.receipt_number, ' · ₹', FORMAT(py.amount, 2), ' · ', IFNULL(py.payment_mode, '')),
                    py.id,
                    'billing',
                    '',
                    IFNULL(py.status, '')
             FROM payments py
             WHERE py.patient_id = ? AND py.deleted_at IS NULL {$payF})
            UNION ALL
            (SELECT 'follow_up',
                    f.follow_up_date,
                    CONCAT('Follow-up · ', REPLACE(IFNULL(f.status, ''), '_', ' '), IF(f.reason IS NOT NULL AND f.reason <> '', CONCAT(' · ', f.reason), '')),
                    f.id,
                    'follow-ups',
                    '',
                    IFNULL(f.status, '')
             FROM follow_ups f
             WHERE f.patient_id = ? AND f.deleted_at IS NULL {$folF})
            ORDER BY event_date DESC, ref_id DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        $params = array_merge(
            [$patientId], $regP,
            [$patientId], $aptP,
            [$patientId], $visP,
            [$patientId], $trtP,
            [$patientId], $preP,
            [$patientId], $payP,
            [$patientId], $folP
        );

        return Database::fetchAll($sql, $params);
    }

    private function nextPatientCode(): string
    {
        $row = Database::fetch('SELECT COUNT(*) c FROM patients');
        return 'PAT' . str_pad(((int) ($row['c'] ?? 0)) + 1, 5, '0', STR_PAD_LEFT);
    }

    private function ageFromDob(?string $dob): ?int
    {
        if (!$dob) {
            return null;
        }
        try {
            $birth = new \DateTime($dob);
            return (int) $birth->diff(new \DateTime())->y;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

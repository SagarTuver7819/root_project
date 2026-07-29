<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;

class ApprovalController extends \App\Core\Controller
{
    use CrudControllerSupport;

    public function index(Request $request): void
    {
        $status = $request->query('status', 'pending');
        $this->view('modules/approvals/index', [
            'title' => 'Approvals',
            'pageTitle' => 'Approval Requests',
            'status' => $status,
            'requests' => Database::fetchAll(
                'SELECT ar.*, u.name AS requested_by_name
                 FROM approval_requests ar
                 LEFT JOIN users u ON u.id = ar.requested_by
                 WHERE ar.status = ?
                 ORDER BY ar.request_date DESC',
                [$status]
            ),
        ]);
    }

    public function approve(Request $request, string $id): void
    {
        $this->decide($request, $id, 'approved');
    }

    public function reject(Request $request, string $id): void
    {
        $this->decide($request, $id, 'rejected');
    }

    private function decide(Request $request, string $id, string $status): void
    {
        $old = Database::fetch('SELECT * FROM approval_requests WHERE id = ?', [$id]);
        if (!$old) {
            $this->jsonError('Approval request not found.', null, 404);
        }
        Database::update('approval_requests', [
            'status' => $status,
            'approved_by' => $this->currentUserId(),
            'approval_date' => $this->now(),
            'remarks' => $request->input('remarks'),
            'updated_at' => $this->now(),
        ], 'id = :_id', ['_id' => (int) $id]);
        $this->audit('approvals', $status, (int) $id, $old, ['status' => $status]);
        $this->finish($request, 'Approval request ' . $status . '.', 'approvals');
    }
}

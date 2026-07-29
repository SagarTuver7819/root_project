<?php

namespace App\Controllers;

use App\Core\DataTable;
use App\Core\Request;

class AuditLogController extends \App\Core\Controller
{
    public function index(Request $request): void
    {
        $this->view('modules/audit-logs/index', ['title' => 'Audit Logs', 'pageTitle' => 'Audit Logs']);
    }

    public function datatable(Request $request): void
    {
        DataTable::make($request, [
            'from' => 'audit_logs al',
            'joins' => ['LEFT JOIN users u ON u.id = al.user_id'],
            'columns' => ['al.id', 'al.created_at', 'u.name AS user_name', 'al.module', 'al.action', 'al.record_id', 'al.ip_address'],
            'searchable' => ['u.name', 'al.module', 'al.action', 'al.record_id', 'al.ip_address'],
            'orderable' => [0 => 'al.id', 1 => 'al.created_at', 3 => 'al.module'],
            'defaultOrder' => ['al.id', 'DESC'],
            'filters' => function (Request $req, array &$where, array &$bindings) {
                if ($req->input('module')) {
                    $where[] = 'al.module = ?';
                    $bindings[] = $req->input('module');
                }
                if ($req->input('date_from')) {
                    $where[] = 'DATE(al.created_at) >= ?';
                    $bindings[] = $req->input('date_from');
                }
                if ($req->input('date_to')) {
                    $where[] = 'DATE(al.created_at) <= ?';
                    $bindings[] = $req->input('date_to');
                }
            },
        ]);
    }
}

<?php

namespace App\Core;

/**
 * Shared server-side DataTables engine.
 *
 * Usage in a *DataTableService:
 *   return DataTable::make($request, [
 *       'from' => 'patients p',
 *       'columns' => [...],
 *       'searchable' => ['p.name', 'p.mobile', 'p.patient_code'],
 *       'defaultOrder' => ['p.id', 'DESC'],
 *       'where' => ['p.deleted_at IS NULL'],
 *       'bindings' => [],
 *   ]);
 */
class DataTable
{
    public static function make(Request $request, array $config): never
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        if ($length < 1 || $length > 100) {
            $length = 10;
        }

        $from = $config['from'];
        $joins = $config['joins'] ?? [];
        $columns = $config['columns']; // list of selectable SQL expressions with aliases
        $searchable = $config['searchable'] ?? [];
        $orderableMap = $config['orderable'] ?? []; // index => column
        $where = $config['where'] ?? [];
        $bindings = $config['bindings'] ?? [];
        $having = $config['having'] ?? [];
        $groupBy = $config['groupBy'] ?? null;
        $defaultOrder = $config['defaultOrder'] ?? ['id', 'DESC'];
        $rowFormatter = $config['rowFormatter'] ?? null;

        $joinSql = $joins ? ' ' . implode(' ', $joins) : '';
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        // Records total (before search)
        $totalSql = "SELECT COUNT(*) AS cnt FROM {$from}{$joinSql}{$whereSql}";
        if ($groupBy) {
            $totalSql = "SELECT COUNT(*) AS cnt FROM (SELECT 1 FROM {$from}{$joinSql}{$whereSql} GROUP BY {$groupBy}) t";
        }
        $recordsTotal = (int) (Database::fetch($totalSql, $bindings)['cnt'] ?? 0);

        // Search
        $searchValue = trim((string) ($request->input('search')['value'] ?? $request->input('search_value', '')));
        $searchBindings = $bindings;
        $searchWhere = $where;

        if ($searchValue !== '' && $searchable) {
            $parts = [];
            foreach ($searchable as $col) {
                $parts[] = "{$col} LIKE ?";
                $searchBindings[] = '%' . $searchValue . '%';
            }
            $searchWhere[] = '(' . implode(' OR ', $parts) . ')';
        }

        // Extra filters from request
        if (!empty($config['filters']) && is_callable($config['filters'])) {
            $config['filters']($request, $searchWhere, $searchBindings);
        }

        $filteredWhereSql = $searchWhere ? ' WHERE ' . implode(' AND ', $searchWhere) : '';
        $havingSql = $having ? ' HAVING ' . implode(' AND ', $having) : '';
        $groupSql = $groupBy ? " GROUP BY {$groupBy}" : '';

        $filteredSql = "SELECT COUNT(*) AS cnt FROM {$from}{$joinSql}{$filteredWhereSql}";
        if ($groupBy) {
            $filteredSql = "SELECT COUNT(*) AS cnt FROM (SELECT 1 FROM {$from}{$joinSql}{$filteredWhereSql}{$groupSql}{$havingSql}) t";
        }
        $recordsFiltered = (int) (Database::fetch($filteredSql, $searchBindings)['cnt'] ?? 0);

        // Ordering
        $orderColIndex = (int) ($request->input('order')[0]['column'] ?? -1);
        $orderDir = strtolower((string) ($request->input('order')[0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $orderColumn = $orderableMap[$orderColIndex] ?? $defaultOrder[0];
        $orderDirection = isset($orderableMap[$orderColIndex]) ? $orderDir : ($defaultOrder[1] ?? 'DESC');

        $select = implode(', ', $columns);
        $dataSql = "SELECT {$select} FROM {$from}{$joinSql}{$filteredWhereSql}{$groupSql}{$havingSql} ORDER BY {$orderColumn} {$orderDirection} LIMIT {$length} OFFSET {$start}";
        $rows = Database::fetchAll($dataSql, $searchBindings);

        if ($rowFormatter) {
            $rows = array_map($rowFormatter, $rows);
        }

        Response::json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }
}

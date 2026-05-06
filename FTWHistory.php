<?php
require_once('../../config.php');

// =========================
// DataTables params
// =========================
$draw   = $_POST['draw'] ?? 1;
$start  = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;
$search = $_POST['search']['value'] ?? '';

$orderBy = "date_created";
$orderDir = "DESC";


// Column mapping
$columns = [
    0 => "tbl_id",
    1 => "date_created",
    2 => "emp_name",
    3 => "emp_dept",
    4 => "job_title",
    5 => "prodline",
    6 => "assessed_by",
    7 => "recommendation",
    8 => "immediate_sup",
    9 => "process_status"
];


// =========================
// Build APVRCHCK (history condition)
// =========================
$LOGINID = $_settings->userdata('EMPLOYID');
$STATION = $_settings->userdata('STATION');
$EMPPOSITION = $_settings->userdata('EMPPOSITION');

$employeeQuery = ($STATION != 'Clinic')
    ? $conn->query("SELECT EMPLOYID FROM employee_masterlist WHERE APPROVER1 = '{$LOGINID}' OR APPROVER2 = '{$LOGINID}' OR EMPLOYID = '{$LOGINID}' ")
    : $conn->query("SELECT EMPLOYID FROM employee_masterlist");

$employeeIds = [];
while ($emp_list = $employeeQuery->fetch_assoc()) {
    $employeeIds[] = $emp_list['EMPLOYID'];
}

$APVRCHCK = "";

if ($EMPPOSITION === '5' || ($STATION !== 'Clinic' && $EMPPOSITION != '1' && strpos($STATION, 'Training') !== false)) {
    // 45 days absent operator approver = supervisor up
    $APVRCHCK .= 'process_status > 1  AND absent_count > 44 AND emp_dept = "Production" OR immediate_sup = 0 OR training_dept = 0';
} elseif ($STATION !== 'Clinic' && $EMPPOSITION == '1' && strpos($STATION, 'Training') !== false) {
    // 45 days absent operator approver not supervisor
    $APVRCHCK .= 'process_status > 1 AND absent_count > 44 AND emp_dept = "Production"';
} elseif ($STATION !== 'Clinic' && $EMPPOSITION != '1') {
    // supervisor
    $APVRCHCK .= 'process_status > 1';
} elseif ($STATION !== 'Clinic' && $EMPPOSITION === '1') {
    // rank and file
    $APVRCHCK .= 'process_status > 0 AND (process_status > 2 AND emp_no = "' . $LOGINID . '")';
} else {
    $APVRCHCK .= 'duty_nurse != 0 AND process_status > 0';
}

if (!empty($employeeIds) && strpos($STATION, 'Training') === false) {
    $employeeIds[] = $LOGINID;
    if ($EMPPOSITION == 5) {
        $APVRCHCK .= " OR emp_no IN ('" . implode("','", $employeeIds) . "')";
    } else {
        $APVRCHCK .= " AND emp_no IN ('" . implode("','", $employeeIds) . "')";
    }
} elseif (!empty($employeeIds) && strpos($STATION, 'Training') !== false) {
    $APVRCHCK .= " OR emp_no IN ('" . implode("','", $employeeIds) . "')";
}

// =========================
// Search filter
// =========================
$searchSql = "";
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $searchSql = " AND (
        ftw.emp_name LIKE '%$search%' 
        OR ftw.emp_dept LIKE '%$search%' 
        OR em.JOB_TITLE LIKE '%$search%' 
        OR em.PRODLINE LIKE '%$search%' 
        OR em2.EMPNAME LIKE '%$search%' 
        OR ftw.recommendation LIKE '%$search%'
    )";
}

// =========================
// Count total and filtered
// =========================
$totalRes = $conn->query("SELECT COUNT(*) as cnt FROM ftw_tbl ftw WHERE $APVRCHCK");
$totalRecords = $totalRes ? intval($totalRes->fetch_assoc()['cnt']) : 0;

$filteredRes = $conn->query("SELECT COUNT(*) as cnt 
                             FROM ftw_tbl ftw
                             LEFT JOIN employee_masterlist em ON em.EMPLOYID = ftw.emp_no
                             LEFT JOIN employee_masterlist em2 ON em2.EMPLOYID = ftw.duty_nurse
                             WHERE $APVRCHCK $searchSql");
$totalFiltered = $filteredRes ? intval($filteredRes->fetch_assoc()['cnt']) : 0;

// =========================
// Fetch paginated rows
// =========================
$sql = "SELECT 
            ftw.tbl_id,
            ftw.emp_name,
            ftw.emp_dept,
            ftw.date_created,
            ftw.process_status,
            ftw.recommendation,
            ftw.emp_no,
            ftw.duty_nurse,
            ftw.absent_count,
            em.JOB_TITLE as job_title,
            em.PRODLINE as prodline,
            em2.EMPNAME as assessed_by,
            em.APPROVER1, em.APPROVER2, em.APPROVER3,
            sup.EMPNAME as immediate_sup
        FROM ftw_tbl ftw
        LEFT JOIN employee_masterlist em ON em.EMPLOYID = ftw.emp_no
        LEFT JOIN employee_masterlist em2 ON em2.EMPLOYID = ftw.duty_nurse
        LEFT JOIN employee_masterlist sup 
            ON sup.EMPLOYID = CASE 
                                WHEN em.APPROVER1 != 'na' THEN em.APPROVER1
                                WHEN em.APPROVER2 != 'na' THEN em.APPROVER2
                                ELSE em.APPROVER3
                              END
        WHERE $APVRCHCK $searchSql
        ORDER BY $orderBy $orderDir
        LIMIT $start, $length";


$res = $conn->query($sql);

// =========================
// Format output
// =========================
$data = [];
$counter = $start + 1;

$recommendArr = [
    1 => 'Fit to work',
    2 => 'Send home',
    3 => 'Send to hospital for further assessment',
    4 => 'Rest (30mins-1hr)',
    5 => 'Unfit to work'
];

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        // determine IM Supervisor

        // Status badge mapping
        if ($row['absent_count'] > 44) {
            $aprvArr = [
                1 => '<span class="badge badge-primary">For Superior Approval</span>',
                2 => '<span class="badge badge-warning">Required Training Approval</span>',
                3 => '<span class="badge badge-success">Completed</span>',
                4 => '<span class="badge badge-success">For Training Approve</span>',
                6 => '<span class="badge badge-danger">Disapproved By Supervisor</span>',
                7 => '<span class="badge badge-danger">Rejected By Employee</span>',
                8 => '<span class="badge badge-danger">Disapproved By Training</span>'
            ];
        } else {
            $aprvArr = [
                0 => '<span class="badge badge-primary">For Nurse Approval</span>',
                1 => '<span class="badge badge-primary">For Superior Approval</span>',
                2 => '<span class="badge badge-warning">Employee Acknowledgement</span>',
                3 => '<span class="badge badge-success">Completed</span>',
                6 => '<span class="badge badge-danger">Disapproved By Supervisor</span>',
                7 => '<span class="badge badge-danger">Rejected By Employee</span>',
                8 => '<span class="badge badge-danger">Disapproved By Training</span>'
            ];
        }

        $statusBadge = $aprvArr[$row['process_status']] ?? '<span class="badge badge-secondary">Unknown</span>';
        $actionBtn = '<a class="btn btn-sm btn-primary" href="?page=fit_to_work/index&v=' . $row['tbl_id'] . '&ti=' . $row['tbl_id'] . '"><span class="fa fa-eye"></span> View</a>';

        $data[] = [
            "id"            => $counter++,
            "date_created"  => date("m-d-Y", strtotime($row['date_created'])),
            "emp_name"      => $row['emp_name'],
            "emp_dept"      => $row['emp_dept'],
            "job_title"     => $row['job_title'],
            "prodline"      => $row['prodline'],
            "assessed_by"   => $row['assessed_by'],
            "recommendation" => $recommendArr[$row['recommendation']] ?? '',
            "immediate_sup" => $row['immediate_sup'] ?? '-',
            "status"        => $statusBadge,
            "action"        => $actionBtn
        ];
    }
}

// =========================
// Return JSON
// =========================
echo json_encode([
    "draw" => intval($draw),
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalFiltered,
    "data" => $data
]);

<?php
require_once('../../config.php');

// DataTables params
$draw   = $_POST['draw'] ?? 1;
$start  = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;
$search = $_POST['search']['value'] ?? '';
$orderColIndex = $_POST['order'][0]['column'] ?? 1;
$orderDir = $_POST['order'][0]['dir'] ?? 'desc';

// Column mapping (must match your <thead> order)
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
$orderBy = $columns[$orderColIndex] ?? "date_created";

// =========================
// Build PROGAPRVR condition (same as your tbody)
// =========================
$LOGINID = $_settings->userdata('EMPLOYID');
$STATION = $_settings->userdata('STATION');
$EMPPOSITION = $_settings->userdata('EMPPOSITION');

$employeeQuery = ($STATION !== 'Clinic')
    ? $conn->query("SELECT EMPLOYID FROM employee_masterlist WHERE APPROVER1 = '{$LOGINID}' OR APPROVER2 = '{$LOGINID}' ")
    : $conn->query("SELECT EMPLOYID FROM employee_masterlist");

$employeeIds = [];
while ($emp_list = $employeeQuery->fetch_assoc()) {
    $employeeIds[] = $emp_list['EMPLOYID'];
}

$PROGAPRVR = "";
$UPDATESTATUS = 0;

if ($STATION !== 'Clinic' && $EMPPOSITION != '1' && strpos($STATION, 'Training') !== false) {
    $PROGAPRVR .= ' duty_nurse != 0 AND absent_count > 44 AND emp_dept = "Production"';
    $UPDATESTATUS = 2;
    $DSAPRVR = 6;
} elseif ($STATION !== 'Clinic' && $EMPPOSITION == '1' && strpos($STATION, 'Training') !== false) {
    $PROGAPRVR .= '(duty_nurse != 0 AND training_dept = 0 AND absent_count > 44 OR duty_nurse != 0 AND process_status = 2 AND emp_no = "' . $LOGINID . '")';
    $UPDATESTATUS = 4;
    $DSAPRVR = 8;
} elseif ($STATION !== 'Clinic' && $EMPPOSITION != '1') {
    $PROGAPRVR .= '(process_status = 2 AND emp_no = "' . $LOGINID . '") OR (immediate_sup = 0 AND duty_nurse != 0 OR process_status = 1 )';
    $UPDATESTATUS = 2;
    $DSAPRVR = 6;
} elseif ($STATION !== 'Clinic' && $EMPPOSITION == '1') {
    $PROGAPRVR .= '(duty_nurse != 0 AND process_status = 2 AND emp_no = "' . $LOGINID . '")';
    $UPDATESTATUS = 3;
    $DSAPRVR = 7;
} else {
    $PROGAPRVR .= '(duty_nurse = 0 AND process_status = 0)';
}

if (!empty($employeeIds) && strpos($STATION, 'Training') === false) {
    $PROGAPRVR .= " AND emp_no IN ('" . implode("','", $employeeIds) . "')";
} elseif (!empty($employeeIds) && strpos($STATION, 'Training') !== false) {
    $PROGAPRVR .= " OR emp_no IN ('" . implode("','", $employeeIds) . "') AND immediate_sup = 0";
}
$ftwQuery = ($STATION !== 'Clinic')
    ? "SELECT emp_name,emp_dept,emp_team,emp_time_in,emp_time_out,tbl_id,process_status,absent_count,date_created,recommendation,emp_no,duty_nurse FROM ftw_tbl WHERE $PROGAPRVR ORDER BY date_created DESC"
    : "SELECT emp_name,emp_dept,emp_team,emp_time_in,emp_time_out,tbl_id,process_status,absent_count,date_created,recommendation,emp_no,duty_nurse FROM ftw_tbl WHERE $PROGAPRVR  ORDER BY date_created DESC";

$FTW_QRY = $conn->query($ftwQuery);
if ($FTW_QRY->num_rows > 0) {
    $showButton = true;
}
// =========================
// Apply search filter
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
$totalRes = $conn->query("SELECT COUNT(*) as cnt FROM ftw_tbl ftw WHERE $PROGAPRVR");
$totalRecords = $totalRes ? intval($totalRes->fetch_assoc()['cnt']) : 0;

$filteredRes = $conn->query("SELECT COUNT(*) as cnt 
                             FROM ftw_tbl ftw
                             LEFT JOIN employee_masterlist em ON em.EMPLOYID = ftw.emp_no
                             LEFT JOIN employee_masterlist em2 ON em2.EMPLOYID = ftw.duty_nurse
                             WHERE $PROGAPRVR $searchSql");
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
            ftw.absent_count,
            ftw.duty_nurse,
            em.JOB_TITLE as job_title,
            em.PRODLINE as prodline,
            em2.EMPNAME as assessed_by,
            em.APPROVER1, em.APPROVER2, em.APPROVER3
        FROM ftw_tbl ftw
        LEFT JOIN employee_masterlist em ON em.EMPLOYID = ftw.emp_no
        LEFT JOIN employee_masterlist em2 ON em2.EMPLOYID = ftw.duty_nurse
        WHERE $PROGAPRVR $searchSql
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
        $IMSUP = null;
        if ($row['APPROVER1'] !== 'na') {
            $IMSUP = $row['APPROVER1'];
        } elseif ($row['APPROVER2'] !== 'na') {
            $IMSUP = $row['APPROVER2'];
        } else {
            $IMSUP = $row['APPROVER3'];
        }

        $statusBadge = '<span class="badge badge-primary">Pending</span>';
        $actionBtn = '';

        // Build action buttons (your existing logic)
        if ($row['absent_count'] > 44 && (strpos($STATION, 'Training') !== false) && $row['emp_dept'] === 'Production' && $EMPPOSITION === '2') {
            $actionBtn = '
        <div class="btn-group">
            <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                Action <span class="sr-only">Toggle Dropdown</span>
            </button>
            <div class="dropdown-menu" role="menu">
                <a class="dropdown-item" href="?page=fit_to_work/index&iv=' . $row['tbl_id'] . '&tr=1&ti=' . $row['tbl_id'] . '">
                    <span class="fa fa-eye"></span> View
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="?page=fit_to_work/index&v=' . $row['tbl_id'] . '&tr=1&ti=' . $row['tbl_id'] . '">
                    <span class="fa-solid fa-check text-success"></span> Approve
                </a>
            </div>
        </div>';
        } elseif (in_array($EMPPOSITION, ['2', '3', '4'])) {
            $actionBtn = '
        <div class="btn-group">
            <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                Action <span class="sr-only">Toggle Dropdown</span>
            </button>
            <div class="dropdown-menu" role="menu">
                <a class="dropdown-item" href="?page=fit_to_work/index&iv=' . $row['tbl_id'] . '&ti=' . $row['tbl_id'] . '&s=1">
                    <span class="fa fa-eye"></span> View
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-success" href="?page=fit_to_work/index&v=' . $row['tbl_id'] . '&s=1&ti=' . $row['tbl_id'] . '">
                    <span class="fa-solid fa-check"></span> Handle Approval
                </a>
            </div>
        </div>';
        } else {
            $actionBtn = '<a class="btn btn-sm btn-primary" href="?page=fit_to_work/index&v=' . $row['tbl_id'] . '&ti=' . $row['tbl_id'] . '">
                <span class="fa fa-eye"></span> View
              </a>';
        }

        // ✅ Add checkbox if EMPPOSITION > 1
        $checkbox = '';
        if ($EMPPOSITION > 1) {
            $checkbox = '
    <div class="checkbox icheck-primary text-center">
        <input 
            type="checkbox" 
            name="chk_single[]" 
            class="chkAll_Row" 
            value="' . $row['tbl_id'] . '" 
            id="chk_' . $row['tbl_id'] . '"
            data-id="' . $row['tbl_id'] . '"
            data-empname="' . $_settings->userdata('EMPLOYID') . '"
            data-status="' . $UPDATESTATUS . '"
            data-dsprv=' . $DSAPRVR . '"
        >
        <label for="chk_' . $row['tbl_id'] . '"></label>
    </div>';
        }


        $rowData = [
            "checkbox"      => $checkbox, // ✅ Add this
            "id"            => $counter++,
            "date_created"  => date("m-d-Y", strtotime($row['date_created'])),
            "emp_name"      => $row['emp_name'],
            "emp_dept"      => $row['emp_dept'],
            "job_title"     => $row['job_title'],
            "prodline"      => $row['prodline'],
            "assessed_by"   => $row['assessed_by'],
            "recommendation" => $recommendArr[$row['recommendation']] ?? '',
            "immediate_sup" => $IMSUP,
            "status"        => $statusBadge,
            "action"        => $actionBtn
        ];



        $data[] = $rowData;
    }
}

// =========================
// Return JSON
// =========================

echo json_encode([
    "draw" => intval($draw),
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalFiltered,
    "showButton" => isset($showButton) ? $showButton : false, // <-- add this
    "data" => $data
]);

<?php
$btn_id = isset($_GET['ti']) ? $_GET['ti'] : '';
$clinic =  isset($_GET['cl']) ? $_GET['cl'] : '';
$supervisor =  isset($_GET['s']) ? $_GET['s'] : '';
$training =  isset($_GET['tr']) ? $_GET['tr'] : '';
$immediate_view = $_GET['iv'] ?? '';
$readonly = (isset($_GET['v']) || !empty($immediate_view)) ? 'readonly' : '';
$CLINICUSER = $_settings->userdata('STATION') == 'Clinic';
$CURRUSER = $_settings->userdata('EMPLOYID');
if ($btn_id) {
    $ftwdata = $conn->query("SELECT * FROM ftw_tbl WHERE tbl_id = '{$btn_id}'");
    while ($ftwrow = $ftwdata->fetch_assoc()):
        foreach ($ftwrow as $k => $v) {
            $ftw_data[$k] = $v;
        }
    endwhile;
    // $calllip_query = $slip_conn->query("SELECT * FROM call_slip_tbl WHERE emp_no = '{$ftw_data['emp_no']}'");
    // while ($slip_row = $calllip_query->fetch_assoc()) {
    //     foreach ($slip_row as $key => $val) {
    //         $callftw_data[$key] = $val;
    //     }
    // }
}

?>
<style>
    .validate {
        border-color: red;
    }

    .custom-form-control {
        width: 100%;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        color: #495057;
        background-color: #fff;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
    }

    .custom-form-control:focus {
        border-color: #80bdff;
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(38, 143, 255, .25);
    }
</style>
<script src="<?php echo base_url ?>plugins/flatpickr/dist/flatpickr.min.js"></script>

<form action="" id="ftw_form">

    <h5 class="mb-4">Fit To Work / Medical Clearance</h5>


    <div class="card card-outline card-success">

        <div class="card-body">
            <input hidden type="number" id="tbl_id" name="tbl_id" value="<?php echo isset($ftw_data['tbl_id']) ? $ftw_data['tbl_id'] : ''; ?>">
            <div class="form-group">
                <div class="row">
                    <?php if (isset($_GET['v'])): ?>
                        <div class="col-md-4">
                            <label for="emp_name">EMPLOYEE NAME</label>
                            <input type="text" class="form-control" id="emp_name" value="<?php echo isset($ftw_data['emp_name']) ? $ftw_data['emp_name'] : ''; ?>" <?php echo $readonly ?>>
                        </div>
                    <?php else: ?>
                        <?php if ($_settings->userdata('STATION') === 'Clinic'): ?>
                            <div class="col-md-4">
                                <label for="emp_name">EMPLOYEE NAME</label>
                                <?php $empList = $conn->query("SELECT EMPNAME, EMPLOYID FROM employee_masterlist WHERE EMPID != 1 AND ACCSTATUS = 1"); ?>
                                <select type="text" class="form-control select2" id="emp_name" name="emp_name" value="">
                                    <option value="" selected>--Select Employee--</option>
                                    <?php while ($emp_Row = $empList->fetch_assoc()): ?>
                                        <option value="<?= $emp_Row['EMPNAME'] ?>"> <?= htmlspecialchars($emp_Row['EMPLOYID'] . ' - ' . $emp_Row['EMPNAME']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="validation-message d-none"><span class="text-danger">Provide answer in this field.</span></div>
                            </div>
                        <?php elseif ($_settings->userdata('EMPPOSITION') > 1): ?>
                            <div class="col-md-4">
                                <label for="emp_name">EMPLOYEE NAME</label>
                                <?php $empList = $conn->query("SELECT EMPNAME, EMPLOYID FROM employee_masterlist WHERE  ACCSTATUS = 1 AND (APPROVER1 = '{$_settings->userdata('EMPLOYID')}' OR APPROVER2 = '{$_settings->userdata('EMPLOYID')}' OR APPROVER3 = '{$_settings->userdata('EMPLOYID')}') "); ?>
                                <select type="text" class="form-control select2" id="emp_name" name="emp_name" value="">
                                    <option value="" selected>--Select Employee--</option>
                                    <?php while ($emp_Row = $empList->fetch_assoc()): ?>
                                        <option value="<?= $emp_Row['EMPNAME'] ?>"> <?= htmlspecialchars($emp_Row['EMPLOYID'] . ' - ' . $emp_Row['EMPNAME']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="validation-message d-none"><span class="text-danger">Provide answer in this field.</span></div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="col-md-4">
                        <label for="emp_team">TEAM</label>
                        <input type="text" class="form-control" id="emp_team" name="emp_team" value="<?php echo isset($ftw_data['emp_team']) ? $ftw_data['emp_team'] : ''; ?>" <?php echo $readonly ?> readonly>
                    </div>
                    <div class="col-md-4">
                        <label for="date_created">DATE</label>
                        <input type="text" class="form-control" id="date_created" value="<?php echo date('m-d-Y'); ?>" <?php echo $readonly ?> readonly>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col-md-4">
                        <label for="emp_no">EMPLOYEE ID</label>
                        <input type="text" class="form-control" id="emp_no" name="emp_no" value="<?php echo isset($ftw_data['emp_no']) ? $ftw_data['emp_no'] : ''; ?>" <?php echo $readonly ?> readonly>
                    </div>
                    <div class="col-md-4">
                        <label for="emp_dept">DEPARTMENT</label>
                        <input type="text" class="form-control" id="emp_dept" name="emp_dept" value="<?php echo isset($ftw_data['emp_dept']) ? $ftw_data['emp_dept'] : ''; ?>" <?php echo $readonly ?> readonly>
                    </div>
                    <?php $DISPLAYSIZE = !empty($ftw_data['emp_time_out']) ? 2 : 4;
                    $DISPLAYTIMEOUT = $ftw_data['emp_time_out'] ?? ''; ?>
                    <div class="col-md-<?= $DISPLAYSIZE ?>">
                        <label for="date_created">TIME IN</label>
                        <input type="time" class="form-control" name="emp_time_in" id="emp_time_in" value="<?php echo isset($ftw_data['emp_time_in']) ? $ftw_data['emp_time_in'] : date('m-d-Y'); ?>" <?php echo $readonly ?>>
                        <div class="validation-message d-none"><span class="text-danger">Provide answer in this field.</span> </div>
                    </div>
                    <?php if ($DISPLAYTIMEOUT != ''): ?>
                        <div class="col-md-2">
                            <label for="date_created">TIME OUT</label>
                            <input type="text" class="form-control" value="<?php echo isset($ftw_data['emp_time_out']) ? date('h:i a', strtotime($ftw_data['emp_time_out'])) : date('m-d-Y'); ?>" <?php echo $readonly ?>>
                            <div class="validation-message d-none"><span class="text-danger">Provide answer in this field.</span></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col-md-12">
                        <label for="emp_diagnose">DIAGNOSIS</label>
                        <textarea name="emp_diagnose" class="form-control" id="emp_diagnose" <?php echo $readonly ?>><?php echo isset($ftw_data['emp_diagnose']) ? $ftw_data['emp_diagnose'] : ''; ?></textarea>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <?php if ($_settings->userdata('STATION') == 'Clinic' || $_settings->userdata('DEPARTMENT') === 'Human Resource'): ?>
                    <div class="row">
                        <div class="col-md-12">
                            <label for="recommendation">RECOMMENDATION</label>
                            <select name="recommendation" id="recommendation" class="form-control select2" <?php echo (isset($_GET['v']) || !empty($immediate_view)) ? 'disabled' : ''; ?>>
                                <option value="0" selected>--Select Recommendation--</option>
                                <option value="1" <?php echo (isset($ftw_data['recommendation']) && $ftw_data['recommendation'] == '1') ? 'selected' : ''; ?>>Fit to work</option>
                                <option value="2" <?php echo (isset($ftw_data['recommendation']) && $ftw_data['recommendation'] == '2') ? 'selected' : ''; ?>>Sent home</option>
                                <option value="3" <?php echo (isset($ftw_data['recommendation']) && $ftw_data['recommendation'] == '3') ? 'selected' : ''; ?>>Send to hospital for further assessment</option>
                                <option value="4" <?php echo (isset($ftw_data['recommendation']) && $ftw_data['recommendation'] == '4') ? 'selected' : ''; ?>>Rest (30mins-1hr)</option>
                                <option value="5" <?php echo (isset($ftw_data['recommendation']) && $ftw_data['recommendation'] == '5') ? 'selected' : ''; ?>>Unfit to work</option>
                                <option value="6" <?php echo (isset($ftw_data['recommendation']) && $ftw_data['recommendation'] == '6') ? 'selected' : ''; ?>>Return to work area</option>

                            </select>
                        </div>
                    </div>
                <?php elseif (!isset($ftw_data['recommendation']) && $_settings->userdata('EMPPOSITION') > 1 && $_settings->userdata('DEPARTMENT') !== 'Human Resource'): ?>
                    <div class="row">
                        <div class="col-md-12">
                            <label for="recommendation">RECOMMENDATIONS</label>
                            <select id="recommendation" class="form-control select2" disabled>
                                <option value="1" selected>Fit to work</option>
                            </select>
                            <input type="hidden" name="recommendation" value="1">
                        </div>
                    </div>
                <?php else: ?>
                    <?php if (isset($_GET['v']) && isset($ftw_data['recommendation'])): ?>
                        <div class="row">
                            <div class="col-md-12">
                                <label for="recommendation">RECOMMENDATIONS</label>
                                <select name="recommendation" id="recommendation" class="form-control select2" <?php echo (isset($_GET['v']) || !empty($immediate_view)) ? 'disabled' : ''; ?>>
                                    <option value="0" selected>--Select Recommendation--</option>
                                    <option value="1" <?php echo (isset($ftw_data['recommendation']) && $ftw_data['recommendation'] == '1') ? 'selected' : ''; ?>>Fit to work</option>
                                    <option value="2" <?php echo (isset($ftw_data['recommendation']) && $ftw_data['recommendation'] == '2') ? 'selected' : ''; ?>>Send home</option>
                                    <option value="3" <?php echo (isset($ftw_data['recommendation']) && $ftw_data['recommendation'] == '3') ? 'selected' : ''; ?>>Send to hospital for further assessment</option>
                                    <option value="4" <?php echo (isset($ftw_data['recommendation']) && $ftw_data['recommendation'] == '4') ? 'selected' : ''; ?>>Rest (30mins-1hr)</option>
                                    <option value="5" <?php echo (isset($ftw_data['recommendation']) && $ftw_data['recommendation'] == '5') ? 'selected' : ''; ?>>Unfit to work</option>
                                    <option value="6" <?php echo (isset($ftw_data['recommendation']) && $ftw_data['recommendation'] == '6') ? 'selected' : ''; ?>>Return to work area</option>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <!-- First Aider Field - Only for EMPLOYID 50005 -->
            <?php if ($_settings->userdata('EMPLOYID') == '50005' || (isset($ftw_data['first_aider_name']) && !empty($ftw_data['first_aider_name']))): ?>
                <div class="form-group mt-3" id="first-aider-group">
                    <label for="first_aider_name" style="width: 100%; padding: 15px 0; font-weight: 500; display: block;">
                        <span>First Aider Name <?php if ($_settings->userdata('EMPLOYID') == '50005' && empty($readonly)): ?><span class="text-danger">*</span><?php endif; ?></span>
                    </label>
                    <input type="text"
                        class="form-control"
                        id="first_aider_name"
                        name="first_aider_name"
                        placeholder="Enter first aider name here..."
                        value="<?php echo isset($ftw_data['first_aider_name']) ? htmlspecialchars($ftw_data['first_aider_name']) : ''; ?>"
                        <?php echo $readonly ?>
                        <?php if ($_settings->userdata('EMPLOYID') == '50005' && empty($readonly)): ?>required<?php endif; ?>>
                    <?php if ($_settings->userdata('EMPLOYID') == '50005' && empty($readonly)): ?>
                        <div class="validation-message d-none"><span class="text-danger">First aider name is required.</span></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="form-group mt-3" id="remarks-group" style="display: none;">
                <label for="remarks" style="width: 100%; padding: 15px 0; letter-spacing: 5px; font-weight: 500; text-transform: uppercase; display: block;">
                    <span>&nbsp;Remarks:</span>
                </label>
                <textarea class="form-control" id="remarks" name="remarks" placeholder="Enter remarks here..." style="height: 100px; width: 100%;" <?php echo (!empty($ftw_data['remarks'])) ? 'disabled' : ''; ?>><?php echo isset($ftw_data['remarks']) ? htmlspecialchars($ftw_data['remarks']) : ''; ?></textarea>
            </div>
            <div class="card">
                <div class="card-body">
                    <h5>
                        <u>Details</u>
                    </h5>
                    <br>
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="emp_shift">SHIFT</label>
                                <select type="number" class="form-control select2" id="emp_shift" name="emp_shift" value="<?php echo isset($ftw_data['emp_shift'])  ? $ftw_data['emp_shift'] : '';  ?>" required <?php echo (isset($_GET['v']) || !empty($clinic)) ? 'disabled' : ''; ?> disabled>
                                    <option value="0" selected>--Select Shifting--</option>
                                    <option value="1" <?php echo (isset($ftw_data['emp_shift']) && $ftw_data['emp_shift'] == '1') ? 'selected' : ''; ?>>Day shift</option>
                                    <option value="2" <?php echo (isset($ftw_data['emp_shift']) && $ftw_data['emp_shift'] == '2') ? 'selected' : ''; ?>>Night shift</option>
                                    <option value="3" <?php echo (isset($ftw_data['emp_shift']) && $ftw_data['emp_shift'] == '3') ? 'selected' : ''; ?>>Normal shift</option>
                                </select>
                            </div>
                            <div class="col-md-6 fittowork d-none">
                                <label for="absent_to">SELECT DATE</label>
                                <input type="text" class="custom-form-control" id="ftw-date" placeholder="Select Date Here" disabled value="<?php echo isset($ftw_data['ftw-date']) ? $ftw_data['ftw-date'] : ''; ?>" required <?php echo $readonly ?> readonly>
                            </div>
                            <div class="col-md-3 fittowork d-none">
                                <label for="absent_count">TOTAL DAYS OF ABSENT</label>
                                <input type="number" class="form-control" id="absent_count" name="absent_count" value="<?php echo isset($ftw_data['absent_count']) ? $ftw_data['absent_count'] : '';  ?>" required <?php echo $readonly ?> readonly>
                            </div>
                            <?php $SDHDATE_TEXT  = isset($ftw_data['sdh_date']) ? 'date' : 'date'; ?>

                            <div class="col-md-3 sdhome d-none">
                                <label for="sdh_date">Date</label>
                                <input type="<?php echo $SDHDATE_TEXT ?>" id="sdh_date" class="form-control" name="sdh_date" value="<?php echo isset($ftw_data['sdh_date']) ? date('Y-m-d', strtotime($ftw_data['sdh_date'])) : ''; ?>">
                            </div>
                            <!-- <div class="col-md-3 sdhome d-none">
                                <label for="sdh_time">Time</label>
                                <input type="time" id="sdh_time" class="form-control" name="sdh_time" value="<?php echo isset($ftw_data['sdh_time']) ? $ftw_data['sdh_time'] : '' ?>">
                            </div> -->
                            <?php //endif; 
                            ?>
                            <div class="col-md-3 rest d-none">
                                <label for="rest_date">Date</label>
                                <?php $RESTDATE_TEXT = isset($ftw_data['rest_date']) ? 'text' : 'date'; ?>
                                <input type="<?php echo $RESTDATE_TEXT ?>" id="rest_date" class="form-control" name="rest_date" value="<?php echo isset($ftw_data['rest_date']) ? date('m-d-Y', strtotime($ftw_data['rest_date'])) : '' ?>">
                            </div>
                            <!-- <div class="col-md-3 rest d-none">
                                <label for="rest_time_in">Time In</label>
                                <input type="time" id="rest_time_in" class="form-control" name="rest_time_in" value="<?php echo isset($ftw_data['rest_time_in']) ? $ftw_data['rest_time_in'] : '' ?>">
                            </div>
                            <div class="col-md-3 rest d-none">
                                <label for="rest_time_out">Time Out</label>
                                <input type="time" id="rest_time_out" class="form-control" name="rest_time_out" value="<?php echo isset($ftw_data['rest_time_out']) ? $ftw_data['rest_time_out'] : '' ?>">
                            </div> -->
                            <?php
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php require_once('../admin/file_transfer_handler/upload_ui.php'); ?>
        </div>
    </div>
    <?php require_once('../admin/fit_to_work/approver_ui.php'); ?>

    <?php if ($clinic && empty($ftw_data['duty_nurse'])): ?>
        <!-- <div class="card-footer">
                    <div class="col-md-4">
                        <input hidden text="number" name="process_status" id="process_status" value="1">
                        <input hidden text="number" name="duty_nurse" id="duty_nurse" value="<?php echo $_settings->userdata('EMPLOYID') ?>">
                        <button type="button" id="ftw_btn" class="btn btn-success">Approved</button>
                    </div>
                </div> -->
    <?php elseif (!empty($supervisor) && empty($ftw_data['immediate_sup']) && empty($immediate_view)): ?>
        <input hidden text="number" name="process_status" id="process_status" value="2">
        <input hidden text="number" name="immediate_sup" id="immediate_sup" value="<?php echo $_settings->userdata('EMPLOYID') ?>">
        <button type="button" id="ftw_btn" class="btn btn-success AprvBtn"><i class="fa-regular fa-circle-check"></i> Approved</button>
    <?php elseif (isset($ftw_data['process_status']) && $ftw_data['process_status'] == 2 && isset($ftw_data['emp_no']) && $CURRUSER === $ftw_data['emp_no']): ?>
        <?php if (($ftw_data['absent_count'] <= 44) || ($ftw_data['absent_count'] > 44 && $ftw_data['training_dept'] != 0)): ?>
            <?php $value = (($ftw_data['absent_count']) > 44) ? 4 : 3; ?>
            <input hidden text="number" name="process_status" id="process_status" value="<?php echo $value; ?>">
            <input hidden text="number" name="ack_by" id="ack_by" value="<?php echo $_settings->userdata('EMPLOYID') ?>">
            <button type="button" id="ftw_btn" class="btn btn-success AprvBtn"><i class="fa-regular fa-circle-check"></i> Acknowledged</button>
        <?php endif; ?>
    <?php elseif (isset($ftw_data['process_status']) && $ftw_data['process_status'] == 2 && isset($ftw_data['emp_no']) && !empty($training)) : ?>
        <input hidden text=" number" name="process_status" id="process_status" value="2">
        <input hidden text="number" name="training_dept" id="training_dept" value="<?php echo $_settings->userdata('EMPLOYID') ?>">
        <button type="button" id="ftw_btn" class="btn btn-success AprvBtn"><i class="fa-regular fa-circle-check"></i> Approved</button>
    <?php endif; ?>
    <?php if (!empty($CLINICUSER) && empty($clinic) && empty($readonly) || $_settings->userdata('EMPPOSITION') > 1 && empty($ftw_data['immediate_sup'])): ?>
        <?php if ($_settings->userdata('STATION') === 'Clinic'): ?>
            <input hidden text="number" name="process_status" id="process_status" value="1">
            <input hidden text="number" name="duty_nurse" id="duty_nurse" value="<?php echo $_settings->userdata('EMPLOYID') ?>">
        <?php endif; ?>
        <?php if ($_settings->userdata('EMPPOSITION') > 1): ?>
            <input hidden text="number" name="process_status" id="process_status" value="2">
            <input hidden text="number" name="immediate_sup" id="immediate_sup" value="<?php echo $_settings->userdata('EMPLOYID') ?>">
            <input hidden text="number" name="duty_nurse" id="duty_nurse" value="<?php echo $_settings->userdata('EMPLOYID') ?>">
        <?php endif; ?>
        <?php if (empty($ftw_data['recommendation'])): ?>
            <!-- <input hidden type="time" class="form-control" name="emp_time_out" id="emp_time_out"> -->
            <button type="button" id="ftw_btn" class="btn btn-secondary">Submit</button> <!-- SUBMIT FORM -->
        <?php endif; ?>
    <?php endif; ?>
    <?php if (empty($_GET['dis']) && !empty($_GET['v']) && (empty($ftw_data['duty_nurse']) && empty($ftw_data['immediate_sup']) && empty($ftw_data['ack_by']) && empty($ftw_data['training_dept']))) { ?>
        <button type="button" id="show_disap" class="btn btn-danger "><i class="fa-regular fa-circle-xmark"></i> Disapproved</button>
    <?php } ?>
</form>
<?php if (empty($_GET['dis'])) { ?>
    <div id="msg"></div>
    <form action="" id="reject_form">
        <input type="hidden" name="tbl_id" value="<?php echo isset($ftw_data['tbl_id']) ? $ftw_data['tbl_id'] : ''; ?>">
        <!-- <div class="card disapprovedCard" style="border-left: 2px solid rgb(255, 183, 183);">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-floating">
                            <textarea class="form-control" id="remarks_by_issuer" name="disapprove_remarks" placeholder=" " style="height: 100px; width: 100%;"></textarea>
                            <label for="remarks_by_issuer" style="width: 100%; padding: 15px 0; letter-spacing: 5px; font-weight: 500; text-transform: uppercase; display: block;">
                                <span>&nbsp;Remarks:</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->
        <?php if (!empty($supervisor) && empty($ftw_data['immediate_sup']) && empty($immediate_view)): ?>
            <div class="col-md-4">
                <input hidden text="number" name="process_status" id="process_status" value="6">
                <input hidden text="number" name="immediate_sup" id="immediate_sup" value="<?php echo $_settings->userdata('EMPLOYID') ?>">
                <button type="button" id="dsprv_btn" class="btn btn-danger disapprovedCard"><i class="fa-regular fa-circle-xmark"></i> Submit</button>
            </div>
        <?php elseif (isset($ftw_data['process_status']) && $ftw_data['process_status'] == 2 && isset($ftw_data['emp_no']) && $CURRUSER === $ftw_data['emp_no']): ?>
            <?php if (($ftw_data['absent_count'] <= 44) || ($ftw_data['absent_count'] > 44 && $ftw_data['training_dept'] != 0)): ?>
                <div class="col-md-4">
                    <?php $value = (($ftw_data['absent_count']) > 44) ? 4 : 3; ?>

                    <input hidden text="number" name="process_status" id="process_status" value="7">
                    <input hidden text="number" name="ack_by" id="ack_by" value="<?php echo $_settings->userdata('EMPLOYID') ?>">
                    <button type="button" id="dsprv_btn" class="btn btn-danger disapprovedCard"><i class="fa-regular fa-circle-xmark"></i> Submit</button>
                </div>
            <?php endif; ?>
        <?php elseif (isset($ftw_data['process_status']) && $ftw_data['process_status'] == 2 && isset($ftw_data['emp_no']) && !empty($training)) : ?>
            <div class="col-md-4">
                <input hidden text="number" name="process_status" id="process_status" value="8">
                <input hidden text="number" name="training_dept" id="training_dept" value="<?php echo $_settings->userdata('EMPLOYID') ?>">
                <button type="button" id="dsprv_btn" class="btn btn-danger disapprovedCard"><i class="fa-regular fa-circle-xmark"></i> Submit</button>
            </div>
        <?php endif; ?>

    </form>
<?php } else { ?>
    <!-- <div class="card" style="border-left: 2px solid rgb(255, 183, 183);">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-floating">
                        <textarea class="form-control" id="remarks_by_issuer" name="disapprove_remarks" placeholder=" " style="height: 100px; width: 100%;" disabled><?php echo isset($ftw_data['disapprove_remarks']) ? $ftw_data['disapprove_remarks'] : ''; ?></textarea>
                        <label for="remarks_by_issuer" style="width: 100%; padding: 15px 0; letter-spacing: 5px; font-weight: 500; text-transform: uppercase; display: block;">
                            <span>&nbsp;Remarks:</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div> -->
<?php } ?>
<input type="hidden" id="viewing_pg" value="<?php echo isset($_GET['v']) ? 1 : 0 ?>">
<script>
    function updateTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0'); // Ensures 2 digits (00-23)
        const minutes = String(now.getMinutes()).padStart(2, '0'); // Ensures 2 digits (00-59)
        const seconds = String(now.getSeconds()).padStart(2, '0'); // Ensures 2 digits (00-59)

        const formattedTime = `${hours}:${minutes}:${seconds}`;
        // document.getElementById("emp_time_outss").value = formattedTime;
    }

    // Update time every second
    setInterval(updateTime, 1000);

    // Set initial value on page load
    updateTime();

    $(document).ready(function() {
        let MultipleValArr = [];
        let countDates = 0;
        let totalDays = 0;
        let DisableDates = {}; // Initially empty

        // Initialize flatpickr
        function initializeFlatpickr() {
            $("#ftw-date").flatpickr({
                mode: "multiple",
                dateFormat: "m-d-Y",
                disable: DisableDates.disable || [], // Use the dynamic disable array
                onChange: function(selectedDates, dateStr, instance) {
                    MultipleValArr = dateStr ? dateStr.split(", ") : []; // Avoids split error on empty selection
                    countDates = MultipleValArr.length;

                    if ($('#emp_shift').val() === '3') {
                        totalDays = countDates * 1; // For emp_shift = 3, set total days to 1
                    } else {
                        totalDays = countDates * 1.5; // Otherwise, calculate as 1.5 days per date
                    }

                    console.log("LIST OF DATES:", MultipleValArr);
                    console.log("ARRAY COUNT:", countDates);
                    console.log("TOTAL DAYS:", totalDays);
                    $('#absent_count').val(totalDays);
                }
            });
        }

        // Event listener for emp_shift change
        $('#emp_shift').on('change', function() {
            if ($('#emp_shift').val() === '') {
                $('#ftw-date').prop('disabled', true);
            } else {
                $('#ftw-date').prop('disabled', false);
            }


            if ($('#emp_shift').val() === '3') { // Compare as string '3'
                // Disable weekends (Saturday and Sunday)
                DisableDates = {
                    disable: [
                        function(date) {
                            return date.getDay() === 0 || date.getDay() === 6; // Disable Sundays (0) and Saturdays (6)
                        }
                    ]
                };
            } else {
                // If it's not shift 3, enable all dates
                DisableDates = {
                    disable: []
                };
            }

            // Reinitialize flatpickr with updated DisableDates
            initializeFlatpickr();
        });

        // Initial flatpickr setup on page load
        initializeFlatpickr();
    });
    $(document).ready(function() {
        // DISSAPPROVED FUNCTION
        $('#dsprv_btn').on('click', function(e) {
            e.preventDefault();
            let Form = new FormData($('#reject_form')[0]);
            $.ajax({
                url: _base_url_ + '/classes/fitowork.php?e=DisapproveForm&d=ftw_tbl',
                data: Form,
                type: 'POST',
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Response :', response);
                    res = JSON.parse(response);
                    if (resp.status == 1) {
                        location.href = './?page=fit_to_work/table';
                    } else {
                        $('#msg').html('<div class="alert alert-danger">Something Wrong</div>')
                        $("html, body").animate({
                            scrollTop: 0
                        }, "fast");
                    }
                }
            });
        });
        $('.disapprovedCard').hide();
        $('#show_disap').on('click', function() {
            console.log($(this).val());
            $('.disapprovedCard').show('fast');
            $('#show_disap').hide('fast');
            $('.AprvBtn').hide('fast');

            $('html, body').animate({
                scrollTop: $('.disapprovedCard').offset().top
            }, 500);
        });


        const recomm = '<?php echo isset($ftw_data['recommendation']) ? $ftw_data['recommendation'] : ''; ?>';
        const viewOnly = '<?php echo isset($_GET['v']) ? $_GET['v'] : ''; ?>';
        const readonly = viewOnly ? 'readonly' : ''; // If 'v' is set, set readonly.
        console.log('RECOMMENDATION :' + recomm);
        // Hide all sections initially
        $('.fittowork, .sdhome, .rest').addClass('d-none');

        // Show the corresponding section based on recommendation
        if (recomm == '1' || recomm == '5') {
            $('.fittowork').removeClass('d-none');
            // Set readonly for inputs inside fittowork if viewOnly
            if (readonly) {
                $('.fittowork input').prop('readonly', true);
            }
        } else if (recomm == '2' || recomm == '3') {
            $('.sdhome').removeClass('d-none');
            // Set readonly for inputs inside sdhome if viewOnly
            if (readonly) {
                $('.sdhome input').prop('readonly', true);
            }
        } else if (recomm == '4') {
            $('.rest').removeClass('d-none');
            // Set readonly for inputs inside rest if viewOnly
            if (readonly) {
                $('.rest input').prop('readonly', true);
            }
        }
    });
</script>

<script>
    $(document).ready(function() {
        // DISABLED FIELD BASE ON THE EMPLOYEE SHIFT
        $('#emp_shift').on('change', function() {
            const shiftVal = $(this).val();
            const AbsFrom = $('#absent_from');
            const AbsTo = $('#absent_to');
            console.log(shiftVal);
            if (shiftVal !== '0') {
                AbsFrom.prop('disabled', false);
                AbsTo.prop('disabled', false);
            } else {
                AbsFrom.prop('disabled', true);
                AbsTo.prop('disabled', true);
            }
        });
        // $('#recommendation').on('change', function() {
        //     const recomm = $(this).val();
        //     if (recomm === '' || recomm === '0') {
        //         $('#emp_shift').prop('disabled', true);
        //     } else {
        //         $('#emp_shift').prop('disabled', false);
        //     }
        //     if (recomm === '1') {
        //         $('.uploadDiv').removeClass('d-none');
        //     } else if (recomm === '4') {
        //         $('.uploadDiv').addClass('d-none');
        //     } else {
        //         $('.uploadDiv').addClass('d-none');

        //     }
        //     const DivArr = {
        //         1: '.fittowork',
        //         2: '.sdhome',
        //         3: '.sdhome',
        //         4: '.rest',
        //     };
        //     $('.fittowork, .sdhome, .rest').addClass('d-none');
        //     if (DivArr[recomm]) {
        //         $(DivArr[recomm]).removeClass('d-none');
        //     }
        // });

    });

    let fileList = [];
    $(document).ready(function() {
        function toggleRemarks() {
            var recVal = $('#recommendation').val();
            if (recVal == '5') {
                $('#remarks-group').show();
            } else {
                $('#remarks-group').hide();
                $('#remarks').val(''); // Optionally clear remarks if not unfit
            }
        }

        // Initial check on page load
        toggleRemarks();

        // Listen for changes
        $('#recommendation').on('change', toggleRemarks);
        //FUNCTION FOR UPLOADING FILE
        $('#upload_id').on('change', function() {
            const filesUpload = this.files;
            Array.from(filesUpload).forEach(file => fileList.push(file));
            updateFileList();
            if (filesUpload.length !== 0) {
                $('.uploadresult').removeClass('d-none');
            } else {
                $('.uploadresult').addClass('d-none');

            }
        });
        console.log(fileList);

        function updateFileList() {
            const tbody = $('#Tbody');
            tbody.empty();
            fileList.forEach((file, index) => {
                tbody.append(`
                <tr>
                   <td>${index + 1}</td>
                    <td>${file.name}</td>
                    <td><button type="button" class="btn btn-primary" onclick="viewFile(fileList[${index}])"><i class="fa-solid fa-eye"></i></button>
                    <button type="button" class="btn btn-danger" onclick="removeFile(${index})"><i class="fa-regular fa-trash-can"></i></button>
                    </td>

                </tr>
            `);
            });
        }

        //VIEWING FILE
        window.viewFile = function(file) {
            const fileURL = URL.createObjectURL(file); // Create object URL
            const fileEmbed = document.getElementById('fileEmbed');
            const imageEmbed = document.getElementById('imageEmbed');
            // Check the file type
            if (file.type.startsWith('image/')) {
                imageEmbed.src = fileURL;
                imageEmbed.style.display = 'block';
                fileEmbed.style.display = 'none';
            } else if (file.type === 'application/pdf') {
                fileEmbed.src = fileURL;
                fileEmbed.style.display = 'block';
                imageEmbed.style.display = 'none';
            }
            $('#fileModal').modal('show'); // Show the modal
        };
        //REMOVE FILE FUNCTION
        window.removeFile = function(index) {
            fileList.splice(index, 1);
            updateFileList();
        };

        //DISABLED SUBMIT BUTTON ONCE THE UPLOAD FIELD IS EMPTY 
        $('#recommendation').on('change', function() {
            let recVal = $(this).val();
            var fileInput = document.getElementById('upload_id');

            if (recVal == 1 || recVal === 5) {
                $('#ftw_btn').attr('disabled', true);
                if (fileInput.files.length !== 0) {
                    $('#ftw_btn').attr('disabled', false);
                } else {
                    $('#ftw_btn').attr('disabled', true);
                }
            } else {
                $('#ftw_btn').attr('disabled', false);
            }
        });
        //DISABLED SUBMIT BUTTON DEPENDS ON DEPENDS ON RECOMMENDATION Eg. ftw it needs file and others is not need to have a file
        $('#upload_id').on('change', function() {
            var fileInput = this;
            var recVal = $('#recommendation').val();
            if (recVal == 1 && fileInput.files.length !== 0 || recVal == 5 && fileInput.files.length !== 0) {
                $('#ftw_btn').attr('disabled', false);
            } else {
                $('#ftw_btn').attr('disabled', true);
            }
        });


        // ✅ Check if all required visible fields are filled
        function checkRequiredFields() {
            let allFieldsFilled = true;

            $('input, select').each(function() {

                // Skip fields with the 'd-none' class (hidden fields)
                if ($(this).closest('.d-none').length > 0 || !$(this).is(':visible')) {
                    return; // Skip this iteration if the field is hidden
                }

                let fieldType = $(this).attr('type'); // Get the type of the input (e.g., text, number, etc.)
                let fieldVal = $(this).val(); // Get the value of the field

                // Check if the field is a select element and has a valid value
                if ($(this).is('select')) {
                    // For select fields, ensure the selected value is not the default (e.g., '0' or '' depending on your setup)
                    if (fieldVal === '0' || fieldVal === '' || fieldVal === null) {
                        allFieldsFilled = false;
                    }
                } else if (fieldType === 'checkbox') {
                    // For checkbox fields, check if it's checked
                    if (!$(this).prop('checked')) {
                        allFieldsFilled = false;
                    }
                } else {
                    // For other input types (text, number, etc.), just check if the field is empty
                    if (fieldVal === '' || fieldVal === null) {
                        allFieldsFilled = false;
                    }
                }
            });

            // 🎨 Update button style based on validation result
            if (allFieldsFilled) {
                $('#ftw_btn').removeClass('btn-secondary').addClass('btn-success');
            } else {
                $('#ftw_btn').removeClass('btn-success').addClass('btn-secondary');
            }
        }

        // 🔄 Re-check fields on input or selection change
        $('input, select').on('input change', function() {
            checkRequiredFields();
        });

        // 🛠️ Initial check on page load
        $(document).ready(function() {
            checkRequiredFields();
        });

        // 🧠 Pre-fill fields and toggle visibility based on recommendation
        if ($('#viewing_pg').val() == 0) {
            if ($('#recommendation').val() == 1 || $('#recommendation').val() == 5) {
                $('#emp_shift').prop('disabled', false);
                $('.uploadDiv').removeClass('d-none');
                $('.fittowork').removeClass('d-none');
            }
        } else {
            $('#emp_shift').prop('disabled', true);

        }

        // 🎯 Update UI based on selected recommendation (shift, file upload, condition display)
        $('#recommendation').on('change', function() {
            const recomm = $(this).val();
            if (recomm === '' || recomm === '0') {
                $('#emp_shift').prop('disabled', true);
            } else {
                $('#emp_shift').prop('disabled', false);
            }
            if (recomm === '1' || recomm === '5') {
                $('.uploadDiv').removeClass('d-none');
            } else if (recomm === '4') {
                $('.uploadDiv').addClass('d-none');
            } else {
                $('.uploadDiv').addClass('d-none');
            }

            const DivArr = {
                1: '.fittowork',
                2: '.sdhome',
                3: '.sdhome',
                4: '.rest',
                5: '.fittowork',
            };

            $('.fittowork, .sdhome, .rest').addClass('d-none');
            if (DivArr[recomm]) {
                $(DivArr[recomm]).removeClass('d-none');
            }

            // After the recommendation change, re-check the fields
            checkRequiredFields();
        });

        // ✅ Validate a single form field
        function validateField(fieldSelector, errorMessage) {
            const field = $(fieldSelector);
            if (field.length === 0) {
                console.error('Field not found:', fieldSelector);
                return false; // Or handle the error as appropriate
            }
            const validationMessage = field.closest('.col-md-2, .col-md-4, .col-md-12').find('.validation-message');

            if (field.val().trim() === '') {
                validationMessage.removeClass('d-none');
                field.addClass('validate');
                alert_toast(errorMessage, 'warning');
                return false;
            } else {
                validationMessage.addClass('d-none');
                field.removeClass('validate');
                return true;
            }
        }

        // 🔄 Update UI and toggle fields based on selected recommendation
        $('#recommendation').on('change', function() {
            let recomm = $(this).val();
            let emp_no = $('#emp_no').val();
            let rename = '';
            if (recomm == 2) {
                rename = 'Send Home';
            } else if (recomm == 3) {
                rename = 'Send to hospital for further assessment';
            }

            // Set the correct label on click, based on the recommendation value
            // $('#ftw_btn').on('click', function() {
            //     if (rename) {
            //         $.ajax({
            //             type: 'GET',
            //             url: _base_url_ + "admin/fit_to_work/send_mail.php?r=" + rename + "&en=" + emp_no,
            //             success: function(response) {
            //                 console.log('Email Sent!!');
            //             }
            //         });
            //     }
            // });
        });

        $('#ftw_btn').on('click', function(e) {
            e.preventDefault();
            const formData = new FormData();
            // Append form fields
            // const TimeIn = $('#emp_time_in').val().trim();
            // const TimeOut = $('#emp_time_out').val().trim();
            // const Empno = $('#emp_no').val().trim();

            if (!validateField('#emp_no', 'Please Provide Employee ID.')) return false;
            if (!validateField('#emp_time_in', 'Please Provide Time In.')) return false;
            if (!validateField('#emp_shift', 'Please Provide Shifting.')) return false;
            if (!validateField('#recommendation', 'Please Provide Recommendation.')) return false;
            // Validate first aider name for user 50005
            if ($('#first_aider_name').length > 0 && $('#first_aider_name').is(':visible')) {
                if (!validateField('#first_aider_name', 'Please provide First Aider Name.')) return false;
            }
            // Enhanced date validation based on recommendation
            const recommendation = $('#recommendation').val();
            let dateFieldSelector = '';
            let dateFieldMessage = '';

            if (recommendation == '1' || recommendation == '5') {
                dateFieldSelector = '#ftw-date';
                dateFieldMessage = 'Please choose the relevant date(s).';
            } else if (recommendation == '4') {
                dateFieldSelector = '#rest_date';
                dateFieldMessage = 'Please provide the rest date.';
            } else if (recommendation == '2' || recommendation == '3') {
                dateFieldSelector = '#sdh_date';
                dateFieldMessage = 'Please provide the send home/hospital date.';
            }

            if (dateFieldSelector && !validateField(dateFieldSelector, dateFieldMessage)) return false;

            if (recommendation == 1 && $('#viewing_pg').val() == 0) {
                if (fileList.length === 0) {
                    alert_toast('Please attach at least one file before submitting.', 'warning');
                    return false;
                }
            }

            formData.append('tbl_id', $('#tbl_id').val());
            formData.append('emp_no', $('#emp_no').val());
            formData.append('emp_name', $('#emp_name').val());
            formData.append('emp_team', $('#emp_team').val());
            formData.append('emp_dept', $('#emp_dept').val());
            formData.append('emp_time_in', $('#emp_time_in').val());
            // formData.append('emp_time_out', $('#emp_time_out').val());
            formData.append('emp_diagnose', $('#emp_diagnose').val());
            formData.append('recommendation', $('#recommendation').val());
            // Append first aider name if field exists
            if ($('#first_aider_name').length > 0) {
                formData.append('first_aider_name', $('#first_aider_name').val());
            }
            formData.append('emp_shift', $('#emp_shift').val());
            var selectedDates = $('#ftw-date').val().split(','); // Split into an array if it's a string
            formData.append('ftw-date', selectedDates.join(','));
            // formData.append('absent_to', $('#absent_to').val());
            formData.append('absent_count', $('#absent_count').val());

            formData.append('sdh_date', $('#sdh_date').val());
            // formData.append('sdh_time', $('#sdh_time').val());

            formData.append('rest_date', $('#rest_date').val());
            // formData.append('rest_time_in', $('#rest_time_in').val());
            // formData.append('rest_time_out', $('#rest_time_out').val());
            formData.append('remarks', $('#remarks').val() || "");
            formData.append('process_status', $('#process_status').val() || 0);

            formData.append('duty_nurse', $('#duty_nurse').val() || 0);
            formData.append('immediate_sup', $('#immediate_sup').val() || 0);
            formData.append('ack_by', $('#ack_by').val() || 0);
            formData.append('training_dept', $('#training_dept').val() || 0);
            // formData.append('date_signed', $('#date_signed').val());
            // Append each selected file
            fileList.forEach(file => formData.append('ftw_file[]', file));
            $.ajax({
                url: _base_url_ + '/classes/save_fileupload.php?x=save_ftw',
                data: formData,
                type: 'POST',
                processData: false,
                contentType: false,
                contentType: false,
                success: function(response) {
                    let resp = JSON.parse(response);
                    console.log(resp);
                    if (resp.status === 'success') {
                        alert_toast(resp.message, 'success');
                        // Use the hash returned from PHP
                        if (recommendation == 2 || recommendation == 3) {
                            let obUrl = "http://192.168.1.28/ob/admin/?page=OB/CreateForm/ftw_ob&h=" + resp.hash;
                            window.open(obUrl, "_blank");
                        }

                        setTimeout(function() {
                            location.replace(_base_url_ + 'admin/?page=fit_to_work/table')
                        }, 1000);
                    } else {
                        alert_toast(resp.message, 'error');
                    }
                },
                error: function(xhr, status) {
                    consoler.error('Something Went Wrong !!', xhr, status);
                }
            });
        });
    });

    // 📆 Calculate absent days based on shift and date range
    document.addEventListener('DOMContentLoaded', function() {
        function calculateDays() {
            const fromDate = document.getElementById('absent_from').value;
            const toDate = document.getElementById('absent_to').value;
            const shift = document.getElementById('emp_shift').value; // Get the employee shift type
            // If both 'from' and 'to' dates are selected, calculate total days
            if (fromDate && toDate) {
                const startDate = new Date(fromDate);
                const endDate = new Date(toDate);
                // If the employee has a normal shift, exclude weekends (Saturday and Sunday)
                let dayCount = 0;
                if (shift == '3') { // Normal Shift (excludes weekends)
                    for (let date = new Date(startDate); date <= endDate; date.setDate(date.getDate() + 1)) {
                        const dayOfWeek = date.getDay();
                        // Check if it's not Saturday (6) or Sunday (0)
                        if (dayOfWeek !== 6 && dayOfWeek !== 0) {
                            dayCount++;
                        }
                    }
                } else {
                    // If it's not a normal shift, count all days
                    const timeDifference = endDate - startDate;
                    rawDayCount = timeDifference / (1000 * 3600 * 24) + 1; // +1 to include the first day
                    dayCount = rawDayCount * 1.5;
                }

                // Update the absent_count input field with the result
                document.getElementById('absent_count').value = dayCount;
            }
        }
        // Add event listeners to the date inputs and shift selection
        document.getElementById('absent_from').addEventListener('change', calculateDays);
        document.getElementById('absent_to').addEventListener('change', calculateDays);
        document.getElementById('emp_shift').addEventListener('change', calculateDays);
        // Initial calculation if both dates are already filled in
        calculateDays(); // Call this initially to handle the default shift case
    });

    // 👤 Autofill employee info based on selected name
    $('#emp_name').on('change', function() {
        const empname = this.value;
        fetch(`${_base_url_}admin/get_data/employee_details.php?e=${empname}`)
            .then(response => response.json())
            .then(resp => {
                if (resp) {
                    // Update the 'emp_team' field with the value from the response
                    document.getElementById('emp_team').value = resp.TEAM;

                    // Update other fields
                    document.getElementById('emp_no').value = resp.EMPLOYID;
                    document.getElementById('emp_dept').value = resp.DEPARTMENT;
                    document.getElementById('prodline').value = resp.PRODLINE;
                } else {
                    console.error('Employee data not found.');
                }
            })
            .catch(error => console.error('Error:', error));
    });
</script>

<!-- WALA NA TO PERO MAY FUNCTION SA LOOB NA NAKA COMMENT PWEDE NYO TRY GAMITIN -->
<script src="<?php echo base_url  ?>admin/FitToWorkJs/ftw.js"></script>
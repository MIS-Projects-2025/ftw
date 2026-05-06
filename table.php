<?php
$LOGINID = $_settings->userdata('EMPLOYID');
$CHCKDEPT = $_settings->userdata('DEPARTMENT');
$EMPPOSITION = $_settings->userdata('EMPPOSITION');
$STATION = $_settings->userdata('STATION');
$showButton = false;
?>
<div class="d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Fit To Work / Medical Clearance</h5>
    <?php if ($_settings->userdata('STATION') == 'Clinic' || $_settings->userdata('EMPPOSITION') > 1): ?>
        <div class="card-tools">
            <a class="btn btn-success" href="?page=fit_to_work/index">
                <i class="fa-solid fa-plus"></i> Create New
            </a>
        </div>
    <?php endif; ?>

</div>
<br>
<div class="col-12 col-sm-12">
    <!-- TABLE HEADER  -->
    <?php $theader = '<thead><tr><th>#</th><th>Date Created</th><th>Employee Name</th><th>Department</th><th>Assessed By</th><th>Recommendation</th><th>Immediate Superior</th><th class="text-center">Status</th><th>Action</th></tr></thead>'; ?>
    <div class="card card-success card-outline card-tabs">
        <div class="card-header p-0 pt-1 border-bottom-0">

            <ul class="nav nav-tabs" id="custom-tabs-three-tab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="custom-tabs-three-pending-tab" data-toggle="pill" href="#custom-tabs-three-pending" role="tab" aria-controls="custom-tabs-three-pending" aria-selected="true"><i class="fa-solid fa-clock-rotate-left"></i>Pending</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="custom-tabs-three-approve-tab" data-toggle="pill" href="#custom-tabs-three-approve" role="tab" aria-controls="custom-tabs-three-approve" aria-selected="false"><i class="fa-solid fa-check-to-slot"></i>History</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="custom-tabs-three-tabContent">
                <div class="tab-pane fade show active" id="custom-tabs-three-pending" role="tabpanel" aria-labelledby="custom-tabs-three-pending-tab">
                    <table class="table table-bordered datatable" id="pending-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <?php if ($EMPPOSITION > 1): ?>
                                    <th class="text-center">
                                        <div class="col-md-2 col-sm-4 col-xs-6 demo-col">
                                            <div class="checkbox icheck-primary">
                                                <input type="checkbox" id="chk_all" name="all_chk" />
                                                <label for="chk_all"></label>
                                            </div>
                                        </div>
                                    </th>
                                <?php endif; ?>
                                <th>#</th>
                                <th>Date Created</th>
                                <th>Employee Name</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Prodline</th>
                                <th>Assessed By</th>
                                <th>Recommendation</th>
                                <th>Immediate Superior</th>
                                <th class="text-center">Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                    </table>
                </div>
                <div class="tab-pane fade" id="custom-tabs-three-approve" role="tabpanel" aria-labelledby="custom-tabs-three-approve-tab">
                    <table class="table table-bordered datatable" id="history-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date Created</th>
                                <th>Employee Name</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Prodline</th>
                                <th>Assessed By</th>
                                <th>Recommendation</th>
                                <th>Immediate Superior</th>
                                <th class="text-center">Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                    </table>

                </div>
            </div>
        </div>
    </div>
</div>
<div class="row justify-content-end" id="approval_buttons" style="display:none;">
    <div class="col-2">
        <button type="button" class="btn btn-success rounded-0 w-100 d-flex align-items-center justify-content-center" id="aprv_btn">
            <i class="fa-solid fa-thumbs-up me-2"></i> Approve
        </button>
    </div>
    <div class="col-2">
        <button type="button" class="btn btn-danger rounded-0 w-100 d-flex align-items-center justify-content-center" id="dprv_btn">
            <i class="fa-solid fa-thumbs-down me-2"></i> Disapprove
        </button>
    </div>
</div>

<script>
    $(document).ready(function() {
        let columnsPending = [];
        <?php if ($EMPPOSITION > 1): ?>
            columnsPending.push({
                data: "checkbox",
                orderable: false,
                searchable: false,
                className: "text-center"
            });
        <?php endif; ?>

        columnsPending = columnsPending.concat([{
                data: "id"
            },
            {
                data: "date_created"
            },
            {
                data: "emp_name"
            },
            {
                data: "emp_dept"
            },
            {
                data: "job_title"
            },
            {
                data: "prodline"
            },
            {
                data: "assessed_by"
            },
            {
                data: "recommendation"
            },
            {
                data: "immediate_sup"
            },
            {
                data: "status",
                className: "text-center"
            },
            {
                data: "action",
                orderable: false,
                searchable: false,
                className: "text-center"
            }
        ]);

        $('#pending-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: _base_url_ + "admin/fit_to_work/FTWPending.php",
                type: "POST",
                data: {
                    status_type: "pending"
                },
                dataSrc: function(json) {
                    // Show/hide approval buttons dynamically
                    const station = '<?php echo $_settings->userdata('STATION'); ?>';
                    const empPosition = '<?php echo $_settings->userdata('EMPPOSITION'); ?>';

                    if ((station === 'Clinic' || empPosition > 1) && json.showButton) {
                        $('#approval_buttons').show();
                    } else {
                        $('#approval_buttons').hide();
                    }

                    return json.data;
                }
            },
            columns: columnsPending
        });



        // History Tab
        $('#history-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: _base_url_ + "admin/fit_to_work/FTWHistory.php",
                type: "POST",
                data: {
                    status_type: "history"
                }
            },
            columns: [{
                    data: "id"
                }, {
                    data: "date_created"
                },
                {
                    data: "emp_name"
                },
                {
                    data: "emp_dept"
                },
                {
                    data: "job_title"
                },
                {
                    data: "prodline"
                },
                {
                    data: "assessed_by"
                },
                {
                    data: "recommendation"
                },
                {
                    data: "immediate_sup"
                },
                {
                    data: "status",
                    className: "text-center"
                },
                {
                    data: "action",
                    orderable: false,
                    searchable: false,
                    className: "text-center"
                }
            ]
        });
    });

    $('#chk_all').on('change', function() {
        let ChkAll = $(this).prop('checked');
        if (ChkAll) {
            $('.chkAll_Row').prop('checked', true);
        } else {
            $('.chkAll_Row').prop('checked', false);
        }
    });

    let dataToSend = [];
    const handleApproval = (btnName) => {
        $('#' + btnName).on('click', function(e) {
            e.preventDefault();
            let dataToSend = [];
            const isApprove = btnName === 'aprv_btn';
            $('.chkAll_Row:checked').each(function() {
                const id = $(this).data('id');
                console.log(id);
                const empname = $(this).data('empname');
                const processStatus = $(this).data('status');
                const disapproveStat = $(this).data('dsprv');
                let fieldName = '';
                let overallStatus = isApprove ? processStatus : disapproveStat;
                if (isApprove) {
                    const approvalFieldMap = {
                        2: 'immediate_sup',
                        3: 'ack_by',
                        4: 'training_dept'
                    };
                    fieldName = approvalFieldMap[processStatus] || 'No Field';
                } else {
                    const disapprovalFieldMap = {
                        6: 'immediate_sup',
                        7: 'ack_by',
                        8: 'training_dept'
                    };
                    fieldName = disapprovalFieldMap[disapproveStat] || 'No Field';
                }

                dataToSend.push({
                    tbl_id: id,
                    fieldname: fieldName,
                    empname: empname,
                    process_status: overallStatus
                });
            });

            if (dataToSend.length === 0) {
                alert_toast('No checkboxes selected', 'warning');
                return false;
            }

            // Ajax request
            $.ajax({
                url: _base_url_ + '/classes/fitowork.php?e=TableSelect&d=ftw_tbl',
                type: 'POST',
                data: {
                    data: dataToSend
                },
                success: function(response) {
                    const parsedResponse = JSON.parse(response);
                    if (parsedResponse.status == '1') {
                        alert_toast('Approved Successfully', 'success');
                        setTimeout(() => location.reload(), 1000);
                    }
                }
            });
        });
    };

    handleApproval('aprv_btn');
    handleApproval('dprv_btn');


    $('#delete_data').click(function() {
        _conf("Are you sure to Delete Request permanently?", "delete_slip", [$(this).attr('data-id')])
    })

    function delete_slip($id) {
        start_loader();
        $.ajax({
            url: _base_url_ + 'classes/crud.php?e=delete_form&d=call_slip_tbl',
            method: "POST",
            data: {
                id: $id
            },
            dataType: "json",
            error: err => {
                console.log(err)
                alert_toast("An error occured.", 'error');
                end_loader();
            },
            success: function(resp) {
                if (typeof resp == 'object' && resp.status == 'success') {
                    location.reload();
                } else {
                    alert_toast("An error occured.", 'error');
                    end_loader();
                }
            }
        })
    }
</script>
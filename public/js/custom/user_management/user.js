(function ($) {
    "use strict";

        var dTable = null;
        var _id = null;

        $(document).ready(function () {
            //load user info datatable
            UserManager.GetDataList(0);

            //generate datatabe serial no
            dTableManager.dTableSerialNumber(dTable);

            //load role dropdown
            UserManager.LoadRoleDropDown();
            UserManager.LoadBranchDropDown();
            UserManager.LoadEmployeeDropDown();

            //save or update
            JsManager.JqBootstrapValidation('#userForm', (form, event) => {
                event.preventDefault();
                if (_id == null) {
                    UserManager.Save(form);
                } else {
                    UserManager.Update(form, _id);
                }
            });

            //add user info modal
            $("#btnAddUser").on("click", function () {
                $(".div-password").find('input').attr('readonly', false);
                $("#frmUserModal").modal('show');
                UserManager.ResetForm();
                _id = null;
            });
        });

        //show edit user info modal
        $(document).on('click', '.dTableEdit', function () {
            var rowData = dTable.row($(this).parent()).data();
            _id = rowData.id;
            $('#name').val(rowData.name);
            $('#email').val(rowData.email);
            $('#username').val(rowData.username);
            $('#sec_role_id').val(rowData.sec_role_id);
            $('#cmn_branch_id').selectpicker('val', rowData.userBranch.map((item) => item.id));
            $('#sch_employee_id').selectpicker('val', rowData.sch_employee_id);
            $(".div-password").find('input').val('00000000');
            if (rowData['status'] == 1) {
                $('#statusYes').prop('checked', true);
            }
            else {
                $('#statusNo').prop('checked', true);
            }            
            setTimeout(() => {
                $("#password_confirmation").focus(); 
            }, 400);
            
            $("#frmUserModal").modal('show');
           
        });

        //delete user info
        $(document).on('click', '.dTableDelete', function () {
            var rowData = dTable.row($(this).parent()).data();
            UserManager.Delete(rowData.id);
        });

        var UserManager = {
            ResetForm: function () {
                $("#userForm").trigger('reset');
                $("#cmn_branch_id").selectpicker('refresh');
            },
            Save: function (form) {
                if (Message.Prompt()) {
                    JsManager.StartProcessBar();
                    var jsonParam = form.serialize() + "&cmn_branch_id=" + $("#cmn_branch_id").val();
                    var serviceUrl = "register-new-user";
                    JsManager.SendJson("POST", serviceUrl, jsonParam, onSuccess, onFailed);

                    function onSuccess(jsonData) {
                        if (jsonData.status == "1") {
                            Message.Success("save");
                            form.trigger('reset');
                            UserManager.GetDataList(1); //reload datatable
                        } else {
                            Message.Error("save");
                        }
                        JsManager.EndProcessBar();

                    }

                    function onFailed(xhr, status, err) {
                        JsManager.EndProcessBar();
                        Message.Exception(xhr);
                    }
                }
            },
            Update: function (form, id) {
                if (Message.Prompt()) {
                    JsManager.StartProcessBar();
                    var jsonParam = form.serialize() + '&id=' + id + "&cmn_branch_id=" + $("#cmn_branch_id").val();
                    var serviceUrl = "update-user-info";
                    JsManager.SendJson("POST", serviceUrl, jsonParam, onSuccess, onFailed);

                    function onSuccess(jsonData) {
                        if (jsonData.status == "1") {
                            Message.Success("update");
                            UserManager.GetDataList(1); //reload datatable
                            _id = null;
                            form.trigger('reset');
                            $("#frmUserModal").modal('hide');
                        } else {
                            Message.Error("update");
                        }
                        JsManager.EndProcessBar();

                    }

                    function onFailed(xhr, status, err) {
                        JsManager.EndProcessBar();
                        Message.Exception(xhr);
                    }
                }
            },
            Delete: function (id) {
                if (Message.Prompt()) {
                    JsManager.StartProcessBar();
                    var jsonParam = { id: id };
                    var serviceUrl = "delete-user-info";
                    JsManager.SendJson("POST", serviceUrl, jsonParam, onSuccess, onFailed);

                    function onSuccess(jsonData) {
                        if (jsonData.status == "1") {
                            Message.Success("delete");
                            UserManager.GetDataList(1); //reload datatable
                        } else {
                            Message.Error("delete");
                        }
                        JsManager.EndProcessBar();

                    }

                    function onFailed(xhr, status, err) {
                        JsManager.EndProcessBar();
                        Message.Exception(xhr);
                    }
                }
            },

            GetDataList: function (refresh) {
                var jsonParam = '';
                var serviceUrl = "get-user-info";
                JsManager.SendJsonAsyncON('GET', serviceUrl, jsonParam, onSuccess, onFailed);

                function onSuccess(jsonData) {
                    UserManager.LoadDataTable(jsonData.data, refresh);
                }

                function onFailed(xhr, status, err) {
                    Message.Exception(xhr);
                }
            },

            LoadDataTable: function (data, refresh) {
                if (refresh == "0") {
                    dTable = $('#tableElement').DataTable({
                        dom: "<'row'<'col-md-6'B><'col-md-3'l><'col-md-3'f>>" + "<'row'<'col-md-12'tr>>" + "<'row'<'col-md-5'i><'col-md-7 mt-7'p>>",
                        initComplete: function () {
                            dTableManager.Border(this, 350);
                        },
                        buttons: [
                            {
                                text: '<i class="fa fa-file-pdf"></i> PDF',
                                className: 'btn btn-sm',
                                extend: 'pdfHtml5',
                                exportOptions: {
                                    columns: [2, 3, 4, 5]
                                },
                                title: 'User List'
                            },
                            {
                                text: '<i class="fa fa-print"></i> Print',
                                className: 'btn btn-sm',
                                extend: 'print',
                                exportOptions: {
                                    columns: [2, 3, 4, 5]
                                },
                                title: 'User List'
                            },
                            {
                                text: '<i class="fa fa-file-excel"></i> Excel',
                                className: 'btn btn-sm',
                                extend: 'excelHtml5',
                                exportOptions: {
                                    columns: [2, 3, 4, 5]
                                },
                                title: 'User List'
                            }
                        ],

                        scrollY: "350px",
                        scrollX: true,
                        scrollCollapse: true,
                        lengthMenu: [[50, 100, 500, -1], [50, 100, 500, "All"]],
                        columnDefs: [
                            { visible: false, targets: [] },
                            { "className": "dt-center", "targets": [3] }
                        ],
                        columns: [
                            {
                                data: null,
                                name: '',
                                'orderable': false,
                                'searchable': false,
                                title: '#SL',
                                width: 8,
                                render: function () {
                                    return '';
                                }
                            },
                            {
                                name: 'Option',
                                title: 'Option',
                                width: 70,
                                render: function (data, type, row) {
                                    return EventManager.DataTableCommonButton();
                                }
                            },
                            {
                                data: 'username',
                                name: 'username',
                                title: 'User Name'
                            },
                            {
                                data: 'email',
                                name: 'email',
                                title: 'Email'
                            },
                            {
                                data: 'role',
                                name: 'role',
                                title: 'User Role'
                            },
                            {
                                data: 'user_type',
                                name: 'user_type',
                                title: 'User Type',
                                render: function (data, type, row) {
                                    let val = "System User";
                                    if (data == 2)
                                        val = "Web User";
                                    return val;
                                }
                            },
                            {
                                data: 'employee',
                                name: 'employee',
                                title: 'Staff For'
                            },
                            {
                                data: 'userBranch',
                                name: 'userBranch',
                                title: 'Branch',
                                render: function (data, type, row) {
                                    var branch = '';
                                    $.each(data, function (i, v) {
                                        branch += v.name + ", ";
                                    })
                                    branch = branch.slice(0, -2);
                                    return branch;
                                }
                            },
                            {
                                data: 'status',
                                name: 'status',
                                title: 'Status',
                                //width: 50,
                                render: function (data, type, row) {
                                    var status = data ? "Active" : "Inactive";
                                    return status;
                                }
                            }
                        ],
                        fixedColumns: false,
                        data: data
                    });
                } else {
                    dTable.clear().rows.add(data).draw();
                }
            },

            LoadRoleDropDown: function () {
                var jsonParam = '';
                var serviceUrl = "get-roles";
                JsManager.SendJson('GET', serviceUrl, jsonParam, onSuccess, onFailed);

                function onSuccess(jsonData) {
                    JsManager.PopulateCombo("#sec_role_id", jsonData.data, "Select Role");
                }

                function onFailed(xhr, status, err) {
                    Message.Exception(xhr);
                }
            },

            LoadBranchDropDown: function () {
                var jsonParam = '';
                var serviceUrl = "get-site-branch";
                JsManager.SendJson('GET', serviceUrl, jsonParam, onSuccess, onFailed);
                function onSuccess(jsonData) {
                    JsManager.PopulateComboSelectPicker("#cmn_branch_id", jsonData.data, 'All');
                }
                function onFailed(xhr, status, err) {
                    Message.Exception(xhr);
                }
            },
            LoadEmployeeDropDown: function () {
                var jsonParam = '';
                var serviceUrl = "get-employee-dropdown";
                JsManager.SendJson('GET', serviceUrl, jsonParam, onSuccess, onFailed);
                function onSuccess(jsonData) {
                    JsManager.PopulateComboSelectPicker("#sch_employee_id", jsonData.data, 'All', '');
                }
                function onFailed(xhr, status, err) {
                    Message.Exception(xhr);
                }
            },
        };
})(jQuery);
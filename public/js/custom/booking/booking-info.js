(function ($) {
    "use strict";
    var dTable = null;
    var _id = null;

    $(document).ready(function () {

        //load datatable
        Manager.GetDataList(0);
        Manager.LoadCustomerDropDown();
        Manager.LoadEmployeeDropDown();
        
        //generate datatabe serial no
        dTableManager.dTableSerialNumber(dTable);

        $("#btnFilter").on("click",function () {
            Manager.GetDataList(1);
        });

        $("#serviceId").on("keyup",function (e) {
            if (e.keyCode == 13) {
                Manager.GetDataList(1);
            }
        });

        //save change status
        JsManager.JqBootstrapValidation('#inputForm', (form, event) => {
            event.preventDefault();
            Manager.ChangeServiceStatus(form);

        });
    });

    //show edit info modal
    $(document).on('click', '.dt-button-action', function () {
        Manager.ResetForm();
        var rowData = dTable.row($(this).parent()).data();
        _id = rowData.id;
        $('#id').val(rowData.id);
        $('#span-booking-no').text(rowData.id);
        $('#status').val(rowData.status);

        $("#frmModal").modal('show');
    });


    var Manager = {
        ResetForm: function () {
            $("#inputForm").trigger('reset');
        },
        ServiceStatus: function (status) {
            var serviceStatus = ['Pending', 'Processing', 'Approved', 'Cancel', 'Done'];
            return serviceStatus[status];
        },
        ServiceFontColorClass: function (status) {
            var serviceColor = ['fc_pending', 'fc_processing', 'fc_approved', 'fc_cancel', 'fc_done'];
            return serviceColor[status];
        },
        ChangeServiceStatus: function (form) {
            if (Message.Prompt()) {
                JsManager.StartProcessBar();
                var jsonParam = form.serialize();
                var serviceUrl = "change-service-booking-status";
                JsManager.SendJson("POST", serviceUrl, jsonParam, onSuccess, onFailed);

                function onSuccess(jsonData) {
                    if (jsonData.status == "1") {
                        Message.Success("Successfully update service status to " + Manager.ServiceStatus($("#status").val()));
                        Manager.ResetForm();
                        $("#frmModal").modal('hide');
                        Manager.GetDataList(1); //reload datatable
                    } else {
                        Message.Error("Failed to update service status for " + Manager.ServiceStatus($("#status").val()));
                    }
                    JsManager.EndProcessBar();
                }
                function onFailed(xhr, status, err) {
                    JsManager.EndProcessBar();
                    Manager.ResetForm();
                    Message.Exception(xhr);
                }
            }
        },
        GetDataList: function (refresh) {
            JsManager.StartProcessBar();
            var jsonParam = {
                dateFrom: $("#dateFrom").val(),
                dateTo: $("#dateTo").val(),
                employeeId: $("#employeeId").val(),
                customerId: $("#customerId").val(),
                serviceStatus: $("#serviceStatus").val(),
                bookingId: $("#serviceId").val()
            };
            var serviceUrl = "get-service-booking-info";
            JsManager.SendJsonAsyncON('GET', serviceUrl, jsonParam, onSuccess, onFailed);

            function onSuccess(jsonData) {
                Manager.LoadDataTable(jsonData.data, refresh);
                JsManager.EndProcessBar();
            }

            function onFailed(xhr, status, err) {
                JsManager.EndProcessBar();
                Message.Exception(xhr);
            }
        },
        LoadDataTable: function (data, refresh) {
            if (refresh == "0") {
                dTable = $('#tableElement').DataTable({
                    dom: "<'row'<'col-md-12'tr>>" + "<'row'<'col-md-4'i><'col-md-3 mt-2'l><'col-md-5 mt-7'p>>",
                    initComplete: function () {
                        dTableManager.Border(this, 450);
                    },
                    buttons: [],

                    scrollY: "450px",
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
                            data: 'id',
                            name: 'id',
                            title: 'No#'
                        },
                        {
                            data: 'date',
                            name: 'date',
                            title: 'Date',
                            render: function (data, type, row) {
                                return moment(data).format('MMM DD, YYYY');
                            }
                        },
                        {
                            data: 'service',
                            name: 'service',
                            title: 'Service Info',
                            render: function (data, type, row) {
                                return '<div class="flex-1 ml-3 pt-1">' +
                                    '<h6 class="text-uppercase fw-bold mb-1">' +
                                    row['service'] +
                                    '<span class="' + Manager.ServiceFontColorClass(row['status']) + ' pl-3">' + Manager.ServiceStatus(row['status']) + '</span>' +
                                    '</h6>' +
                                    '<span class="text-muted">' +
                                    row['customer'] + " | " + row['customer_phone_no'] + " | <span class='text-primary'>" + moment(row['date'] + ' ' + row['start_time']).format('LT') + " to " + moment(row['date'] + ' ' + row['end_time']).format('LT') + "</span><br/>" +
                                    "Due# <span class='text-danger'>" + parseFloat(row['due']).toFixed(2) + "</span> | " + (row['remarks'] == null ? "No remarks found!" : row['remarks'])
                                    + '</span>' +
                                    '</div>';
                            }
                        },
                        {
                            data: 'branch',
                            name: 'branch',
                            title: 'Branch'
                        },
                        {
                            data: 'employee',
                            name: 'employee',
                            title: 'Staff/Employee'
                        },
                        {
                            name: 'Option',
                            title: 'Option',
                            width: 70,
                            render: function (data, type, row) {
                                return '<button class="btn btn-sm btn-primary dt-button-action"><i class="fas fa-location-arrow"></i> Action</button>';
                            }
                        },
                    ],
                    fixedColumns: false,
                    data: data
                });
            } else {
                dTable.clear().rows.add(data).draw();
            }
        },
        LoadEmployeeDropDown: function () {
            var jsonParam = { branchId: 0 };
            var serviceUrl = "get-employee-dropdown";
            JsManager.SendJson('GET', serviceUrl, jsonParam, onSuccess, onFailed);

            function onSuccess(jsonData) {
                if (jsonData.data.length < 2) {
                    JsManager.PopulateComboSelectPicker("#employeeId", jsonData.data);
                } else {
                    JsManager.PopulateComboSelectPicker("#employeeId", jsonData.data, 'All Employee');
                }
                $("#employeeId").selectpicker('refresh');
            }

            function onFailed(xhr, status, err) {
                Message.Exception(xhr);
            }
        },
        LoadCustomerDropDown: function () {
            var jsonParam = '';
            var serviceUrl = "get-customer-dropdown";
            JsManager.SendJson('GET', serviceUrl, jsonParam, onSuccess, onFailed);

            function onSuccess(jsonData) {
                JsManager.PopulateComboSelectPicker("#customerId", jsonData.data, 'All Customer');
                $("#customerId").selectpicker('refresh');
            }
            function onFailed(xhr, status, err) {
                Message.Exception(xhr);
            }
        },
    };
})(jQuery);
﻿
(function ($) {
    "use strict";
    var dTable = null;
    var _id = null;

    $(document).ready(function () {

        //load datatable
        Manager.GetDataList(0);

        //generate datatabe serial no
        dTableManager.dTableSerialNumber(dTable);

        //add  modal
        $("#btnAdd").on("click",function () {
            _id = null;
            Manager.ResetForm();
            $("#frmModal").modal('show');
        });

        //save or update
        JsManager.JqBootstrapValidation('#inputForm', (form, event) => {
            event.preventDefault();
            if (_id == null) {
                Manager.Save(form);
            } else {
                Manager.Update(form, _id);
            }
        });

    });

    //show edit info modal
    $(document).on('click', '.dTableEdit', function () {
        var rowData = dTable.row($(this).parent()).data();
        _id = rowData.id;
        $('#name').val(rowData.name);
        $('#order').val(rowData.order);

        $("#frmModal").modal('show');
    });


    //delete
    $(document).on('click', '.dTableDelete', function () {
        var rowData = dTable.row($(this).parent()).data();
        Manager.Delete(rowData.id);
    });


    var Manager = {
        ResetForm: function () {
            $("#inputForm").trigger('reset');
        },
        Save: function (form) {
            if (Message.Prompt()) {
                JsManager.StartProcessBar();
                var jsonParam = form.serialize();
                var serviceUrl = "department-save";
                JsManager.SendJson("POST", serviceUrl, jsonParam, onSuccess, onFailed);

                function onSuccess(jsonData) {
                    if (jsonData.status == "1") {
                        Message.Success("save");
                        Manager.ResetForm();
                        Manager.GetDataList(1); //reload datatable
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
                var jsonParam = form.serialize() + "&id=" + id;
                var serviceUrl = "department-update";
                JsManager.SendJson("POST", serviceUrl, jsonParam, onSuccess, onFailed);

                function onSuccess(jsonData) {
                    if (jsonData.status == "1") {
                        Message.Success("update");
                        _id = null;
                        Manager.ResetForm();
                        Manager.GetDataList(1); //reload datatable
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
                var serviceUrl = "department-delete";
                JsManager.SendJson("POST", serviceUrl, jsonParam, onSuccess, onFailed);

                function onSuccess(jsonData) {
                    if (jsonData.status == "1") {
                        Message.Success("delete");
                        Manager.GetDataList(1); //reload datatable
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
            var serviceUrl = "get-department";
            JsManager.SendJsonAsyncON('GET', serviceUrl, jsonParam, onSuccess, onFailed);

            function onSuccess(jsonData) {
                Manager.LoadDataTable(jsonData.data, refresh);
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
                                columns: [2, 3]
                            },
                            title: 'Department List'
                        },
                        {
                            text: '<i class="fa fa-print"></i> Print',
                            className: 'btn btn-sm',
                            extend: 'print',
                            exportOptions: {
                                columns: [2, 3]
                            },
                            title: 'Department List'
                        },
                        {
                            text: '<i class="fa fa-file-excel"></i> Excel',
                            className: 'btn btn-sm',
                            extend: 'excelHtml5',
                            exportOptions: {
                                columns: [2, 3]
                            },
                            title: 'Department List'
                        }
                    ],

                    scrollY: "350px",
                    scrollX: true,
                    scrollCollapse: true,
                    lengthMenu: [[50, 100, 500, -1], [50, 100, 500, "All"]],
                    columnDefs: [
                        { visible: false, targets: [] },
                        { "className": "dt-center", "targets": [4] }
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
                            data: 'name',
                            name: 'name',
                            title: 'Name'
                        },

                        {
                            data: 'order',
                            name: 'order',
                            title: 'Order'
                        },
                        {
                            data: 'total_employee',
                            name: 'total_employee',
                            title: 'Total Employee',
                            width:150,
                            render:function(data,type,row){
                                return '<span class="badge badge-success">'+data+'</span>'
                            }
                        }
                    ],
                    fixedColumns: false,
                    data: data
                });
            } else {
                dTable.clear().rows.add(data).draw();
            }
        }
    };
})(jQuery);
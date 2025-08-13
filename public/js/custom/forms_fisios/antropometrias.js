(function ($) {
    "use strict";
    var dTable = null;
    var _id = null;
    var initTelephone;
    $(document).ready(function () {

        //load datatable
        Manager.GetDataList(0);
        Manager.LoadUserDropdown();
        Manager.LoadPatientDropDown();

        //generate datatabe serial no
        dTableManager.dTableSerialNumber(dTable);

        //add  modal
        $("#btnAdd").on("click", function () {
            _id = null;
            //Mostrar y ocultar nombre paciente y busqueda
            patientDiv.style.display = 'block';
            NompatientDiv.style.display = 'none';
            Manager.ResetForm();
            $("#frmModal1").modal('show');
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

   // Show edit info modal
$(document).on('click', '.dTableEdit', function () {
    var rowData = dTable.row($(this).parent()).data();
    console.log(rowData);
    _id = rowData.id;

    //Mostrar y ocultar nombre paciente y busqueda
    patientDiv.style.display = 'none';
    NompatientDiv.style.display = 'block';

    // Definir campos que son checkbox
    const checkboxFields = [
        // EQUILIBRIO SENTADO
        'equi_s', 'equi_f',
        // LEVANTARSE
        'lev_i', 'lev_c', 'lev_ca',
        // INTENTO DE LEVANTARSE
        'int_i', 'int_c', 'int_ca',
        // EQUILIBRIO INMEDIATO AL LEVANTARSE
        'equil_i', 'equil_e', 'equil_es',
        // EQUILIBRIO EN BIPEDESTACIÓN
        'equib_i', 'equib_e', 'equib_b',
        // EMPUJON
        'em_t', 'em_s', 'em_f',
        // OJOS CERRADOS
        'oj_i', 'oj_e',
        // GIRO DE 360
        'gir_p', 'gir_pa',
        // SENTARSE
        'se_i', 'se_u', 'se_s'
    ];

    // Agregar hidden inputs para checkboxes
    checkboxFields.forEach(field => {
        let checkbox = $('[name="' + field + '"]');
        if (checkbox.length && !checkbox.prev('input[type="hidden"]').length) {
            checkbox.before('<input type="hidden" name="' + field + '" value="0">');
        }
    });

    // Asignación automática de valores
    Object.keys(rowData).forEach(key => {
        const input = $('[name="' + key + '"]');
        if (input.length) {
            if (checkboxFields.includes(key)) {
                // Checkbox → marcar/desmarcar
                input.filter('[type="checkbox"]').prop('checked', rowData[key] == 1);
            } else {
                // Otros campos
                input.val(rowData[key]);
            }
        }
    });

    $('#id').val(_id);
    $('#frmModal1').modal('show');
});

// Limpiar formulario al cerrar modal
$('#frmModal1').on('hidden.bs.modal', function () {
    this.reset();
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
                 $(form).find('input[type=checkbox]').each(function () {
                    if ($(this).is(':checked')) {
                        var hiddenInput = $(form).find('input[type=hidden][name="' + this.name + '"]');
                        hiddenInput.remove(); // elimina el duplicado
                    }
                });

                JsManager.StartProcessBar();
                var jsonParam = form.serialize();
                var serviceUrl = "antropometrias-create";
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
                $(form).find('input[type=checkbox]').each(function () {
                    if ($(this).is(':checked')) {
                        var hiddenInput = $(form).find('input[type=hidden][name="' + this.name + '"]');
                        hiddenInput.remove(); // elimina el duplicado
                    }
                });
                JsManager.StartProcessBar();
                var jsonParam = form.serialize();
                var serviceUrl = "antropometrias-update";
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
                var serviceUrl = "antropometrias-delete";
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

        LoadUserDropdown: function () {
            var jsonParam = '';
            var serviceUrl = "get-customer-user";
            JsManager.SendJson('GET', serviceUrl, jsonParam, onSuccess, onFailed);

            function onSuccess(jsonData) {
                var cbmOptions = '<option value="">Unknown User</option>';
                cbmOptions += '<option value="0">Create System User(Pass:12345678)</option>';
                $.each(jsonData.data, function () {
                    cbmOptions += '<option value=\"' + this.id + '\">' + this.name + '</option>';
                });
                $("#user_id").html(cbmOptions);
            }

            function onFailed(xhr, status, err) {
                Message.Exception(xhr);
            }
        },
        LoadPatientDropDown: function (nowInsertedCustomerId) { 
            var jsonParam = '';
            var serviceUrl = "get-patient-dropdown";
            JsManager.SendJson('GET', serviceUrl, jsonParam, onSuccess, onFailed);

            function onSuccess(jsonData) {
                JsManager.PopulateComboSelectPicker("#patient_id", jsonData.data, "Select One", '', nowInsertedCustomerId);
                JsManager.PopulateComboSelectPicker("#filter_patient_id", jsonData.data, "All Customer", '0');
                $("#patient_id").selectpicker('refresh');
                $("#filter_patient_id").selectpicker('refresh');
            }
            function onFailed(xhr, status, err) {
                Message.Exception(xhr);
            }
        },
        GetDataList: function (refresh) {
            var jsonParam = '';
            var serviceUrl = "get-antropometrias"; // cambiar
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
                                columns: [2, 3, 4, 5]
                            },
                            title: 'Anthropometry physical therapy List' // cambiar
                        },
                        {
                            text: '<i class="fa fa-print"></i> Print',
                            className: 'btn btn-sm',
                            extend: 'print',
                            exportOptions: {
                                columns: [2, 3, 4, 5]
                            },
                            title: 'Anthropometry physical therapy List'// cambiar
                        },
                        {
                            text: '<i class="fa fa-file-excel"></i> Excel',
                            className: 'btn btn-sm',
                            extend: 'excelHtml5',
                            exportOptions: {
                                columns: [2, 3, 4, 5]
                            },
                            title: 'Anthropometry physical therapy List'// cambiar
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
                            width: 60,
                            render: function (data, type, row) {
                                return EventManager.DataTableCommonButton();
                            }
                        },
                        {
                            data: 'fecha',
                            name: 'Fecha',
                            title: 'Fecha'
                        },
                        {
                            data: 'customer_name',
                            name: 'customer_name',
                            title: 'customer name'
                        },
                        {
                            data: 'name_user',
                            name: 'name_user',
                            title: 'Encargado'
                        },
                       /** 
                        {
                            data: 'observaciones',
                            name: 'Observaciones',
                            title: 'Observaciones'
                        }
                             **/
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
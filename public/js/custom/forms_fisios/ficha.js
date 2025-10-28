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

        patientDiv.style.display = 'block';
        NompatientDiv.style.display = 'none';

        //add  modal
        $("#btnAdd").on("click", function () {
            _id = null;
            //Mostrar y ocultar nombre paciente y busqueda
            
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

// Evento para imprimir la ficha técnica con el formato del formulario de edición laestrada
    $(document).on('click', '.dTableView', function () {
        var rowData = dTable.row($(this).closest('tr')).data();
        if (!rowData) return;

        // Clona el modal de edición
        var modalClone = $('#frmModal1').clone();

        // Oculta elementos no imprimibles
        modalClone.find('.modal-header, .modal-footer, .alert, button, .input-group-append').remove();
        // Ocultar búsqueda paciente, mostrar solo nombre
        modalClone.find('#patientDiv').hide();
        modalClone.find('#NompatientDiv').hide();
        modalClone.find('#DatosImpresion').show();

        // Asigna los valores de rowData a los campos del formulario clonado
        const checkboxFields = [
            'modalidades_ejercicio_terapeutico',
            'modalidades_electroterapia',
            'modalidades_masoterapia',
            'modalidades_estiramientos',
            'modalidades_tecaterapia',
            'modalidades_puncion_seca',
            'modalidades_electropuncion'
        ];
        ['c', 't', 'l', 's'].forEach(prefix => {
            for (let i = 1; i <= 12; i++) {
                ['zn', 'zs', 'za'].forEach(suffix => {
                    checkboxFields.push(`${prefix}${i}_${suffix}`);
                });
            }
        });       

        Object.keys(rowData).forEach(function (key) {
            var input = modalClone.find('[name="' + key + '"]');
            if (input.length) {
                if (checkboxFields.includes(key)) {
                    input.prop('checked', rowData[key] == 1);
                } else {
                    input.val(rowData[key]);
                }
            }
        });

        // Convierte todos los inputs, selects y textareas en texto subrayado
        modalClone.find('input, select, textarea').each(function () {
            var $el = $(this);
            if ($el.is('input[type="hidden"]')) {
                $el.remove();
                return;
            }
            var text;
            if ($el.is(':checkbox')) {
                text = $el.is(':checked') ? '✔' : '✘';
            } else {
                text = $el.val() || '';
            }
            $el.replaceWith($('<span style="border-bottom:1px solid #222;min-width:120px;display:inline-block;padding:2px 6px;">').text(text));
        });

        // Aplica formato de ficha técnica
        var htmlPrint = `
        <html>
        <head>
            <title>Ficha Técnica</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 30px;
                    background: #fff;
                }
                .modal-content {
                    width: 100%;
                    max-width: 900px;
                    border: none !important;
                    box-shadow: none !important;
                }
                .modal-title, legend, h4, h5 {
                    font-size: 1.4em;
                    font-weight: bold;
                    color: #2a3f54;
                    margin-bottom: 12px;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }
                label {
                    font-weight: bold;
                    color: #333;
                    min-width: 160px;
                    display: inline-block;
                }
                span[style*="border-bottom"] {
                    font-size: 1em;
                    color: #222;
                    margin-bottom: 6px;
                }
                .form-group, .row, .col-md-6, .col-md-12 {
                    margin-bottom: 8px !important;
                }
                .form-control, .form-select {
                    display: none !important;
                }
            </style>
        </head>
        <body>
            ${modalClone.find('.modal-content')[0].outerHTML}
        </body>
        </html>
        `;

        // Abre la ventana de impresión
        var ventana = window.open('', '', 'width=900,height=700');
        ventana.document.write(htmlPrint);
        ventana.document.close();
        ventana.focus();

        ventana.onload = function () {
            ventana.print();
            ventana.close();
        };
    });
    // final evento imprimir ficha técnica laestrada


    // Show edit info modal
    $(document).on('click', '.dTableEdit', function () {
        var rowData = dTable.row($(this).parent()).data();
        _id = rowData.id;
        

        //Mostrar y ocultar nombre paciente y busqueda
        patientDiv.style.display = 'none';
        NompatientDiv.style.display = 'block';
        DatosImpresion.style.display = 'none';

        // Definir campos que son checkbox
            const checkboxFields = [
                 // Modalidades
                'modalidades_ejercicio_terapeutico',
                'modalidades_electroterapia',
                'modalidades_masoterapia',
                'modalidades_estiramientos',
                'modalidades_tecaterapia',
                'modalidades_puncion_seca',
                'modalidades_electropuncion'
            ];

            ['c', 't', 'l', 's'].forEach(prefix => {
                for (let i = 1; i <= 12; i++) {
                    ['zn', 'zs', 'za'].forEach(suffix => {
                        checkboxFields.push(`${prefix}${i}_${suffix}`);
                    });
                }
            });

        // Asignación automática a inputs que coincidan con los nombres de las claves
            Object.keys(rowData).forEach(function (key) {
                const input = $('[name="' + key + '"]');
                if (input.length) {
                    if (checkboxFields.includes(key)) {
                        input.prop('checked', rowData[key] == 1); // ✅ checkbox
                    } else {
                        input.val(rowData[key]); // ✅ campos normales
                    }
                }
            });
        $('#id').val(_id);
        $('#frmModal1').modal('show');
    });


    //delete
    $(document).on('click', '.dTableDelete', function () {
        var rowData = dTable.row($(this).parent()).data();
        Manager.Delete(rowData.id);
    });

     // ===========================
    // NUEVO BOTÓN: seguimiento
    // ===========================
   $(document).on('click', '.dTableSeguimiento', function() {
    var rowData = dTable.row($(this).closest('tr')).data();
    $('#formSeguimiento')[0].reset();
    $('#seguimiento_id').val('');
    $('#seguimiento_patient_id').val(rowData.patient_id);
    $('#ficha_id').val(rowData.id);
    $('#modalSeguimiento').modal('show');
});

    // ===========================
    // NUEVO BOTÓN: ver seguimiento
    // ===========================
$(document).on('click', '.dTableVerSeguimiento', function() {
    var rowData = dTable.row($(this).closest('tr')).data();
    var container = $('#seguimientoContent');
    container.empty();

    if (rowData.seguimientos && rowData.seguimientos.length > 0) {
        var tableHtml = `
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Tratamiento</th>
                        <th>Observaciones</th>
                        <th>Evolución</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
        `;

        rowData.seguimientos.forEach(function(seg, index) {
            tableHtml += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${seg.fecha}</td>
                    <td>${seg.tratamiento_realizado || ''}</td>
                    <td>${seg.observaciones || ''}</td>
                    <td>${seg.evolucion || ''}</td>
                    <td>
                            <button class="btn btn-warning btn-sm btnEditarSeguimiento" data-id="${seg.id}" data-ficha-id="${seg.ficha_id}" data-patient-id="${seg.patient_id}"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-danger btn-sm btnEliminarSeguimiento" data-id="${seg.id}"><i class="fas fa-trash"></i></button>
                        </td>
                </tr>
            `;
        });

        tableHtml += `
                </tbody>
            </table>
        `;

        container.html(tableHtml);
    } else {
        container.html('<p class="text-center text-muted">No hay seguimientos registrados para este paciente.</p>');
    }

    $('#modalVerSeguimiento').modal('show');
});


    // ===========================
    // EDITAR SEGUIMIENTO
    // ===========================
$(document).on('click', '.btnEditarSeguimiento', function() {
    var id = $(this).data('id');
    var seguimiento = null;

    // Buscar seguimiento en los datos del DataTable
    dTable.rows().data().each(function(row) {
        if(row.seguimientos){
            seguimiento = row.seguimientos.find(s => s.id == id);
        }
    });

    if(seguimiento){
        $('#formSeguimiento')[0].reset();
        $('#seguimiento_id').val(seguimiento.id);
        $('#ficha_id').val(seguimiento.ficha_id);
        $('#seguimiento_patient_id').val(seguimiento.patient_id);
        $('[name="fecha"]').val(seguimiento.fecha);
        $('[name="tratamiento_realizado"]').val(seguimiento.tratamiento_realizado);
        $('[name="observaciones"]').val(seguimiento.observaciones);
        $('[name="evolucion"]').val(seguimiento.evolucion);

        $('#modalSeguimiento').modal('show');
        $('#modalVerSeguimiento').modal('hide'); 
    }
});


    // ===========================
    // ELIMINAR SEGUIMIENTO
    // ===========================
$(document).on('click', '.btnEliminarSeguimiento', function() {
            var id = $(this).data('id');
            if (Message.Prompt()) {
                JsManager.StartProcessBar();
                JsManager.SendJson("POST", "seguimiento-delete/" + id, {}, function(jsonData) {
                    JsManager.EndProcessBar();
                    if (jsonData.status == "1") {
                        Message.Success("delete");
                        Manager.GetDataList(1);
                        $('#modalVerSeguimiento').modal('hide');
                    } else {
                        Message.Error("delete");
                    }
                }, function(xhr, status, err) {
                    JsManager.EndProcessBar();
                    Message.Exception(xhr);
                });
            }
        });
    // ===========================
    // GUARDAR / ACTUALIZAR SEGUIMIENTO
    // ===========================
    $("#formSeguimiento").on("submit", function(e) {
    e.preventDefault();
    var form = $(this);
    JsManager.StartProcessBar();

    var seguimientoId = $('#seguimiento_id').val();
    var serviceUrl = seguimientoId ? "seguimiento/update/" + seguimientoId : "seguimiento-create";
    var method = seguimientoId ? "POST" : "POST"; // Laravel usa POST para ambos

    JsManager.SendJson(method, serviceUrl, form.serialize(), function(jsonData){
        JsManager.EndProcessBar();
        if(jsonData.status == "1") {
            Message.Success(seguimientoId ? "update" : "save");
            $('#modalSeguimiento').modal('hide');
            form.trigger('reset');
            Manager.GetDataList(1); // recargar tabla principal
        } else {
            Message.Error(seguimientoId ? "update" : "save");
        }
    }, function(xhr, status, err){
        JsManager.EndProcessBar();
        Message.Exception(xhr);
    });
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
                var serviceUrl = "ficha-create";
                JsManager.SendJson("POST", serviceUrl, jsonParam, onSuccess, onFailed);

                function onSuccess(jsonData) {
    JsManager.EndProcessBar();

    if (jsonData.status == "1") {
        Message.Success("save");

        // Redirigir si viene la URL
        if (jsonData.redirect) {
            window.location.href = jsonData.redirect;
            return; // detener ejecución adicional
        }

        Manager.ResetForm();
        Manager.GetDataList(1); // reload datatable
    } else {
        Message.Error("save");
    }
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
                var serviceUrl = "ficha-update";
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
                var serviceUrl = "ficha-delete";
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
            var serviceUrl = "get-ficha"; // cambiar
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
                            title: 'Ficha' // cambiar
                        },
                        {
                            text: '<i class="fa fa-print"></i> Print',
                            className: 'btn btn-sm',
                            extend: 'print',
                            exportOptions: {
                                columns: [2, 3, 4, 5]
                            },
                            title: 'Ficha'// cambiar
                        },
                        {
                            text: '<i class="fa fa-file-excel"></i> Excel',
                            className: 'btn btn-sm',
                            extend: 'excelHtml5',
                            exportOptions: {
                                columns: [2, 3, 4, 5]
                            },
                            title: 'Ficha'// cambiar
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
                                return EventManager.DataTableCommonButton2 ();
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
                        {
                            data: 'motivo_consulta',
                            name: 'Motivo Consulta',
                            title: 'Motivo Consulta'
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
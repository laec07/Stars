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
            DatosImpresion.style.display = 'none';
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
        DatosImpresion.style.display = 'none';

        // Definir campos que son checkbox
            const checkboxFields = [];

            ['c', 't', 'l', 's'].forEach(prefix => {
                for (let i = 1; i <= 12; i++) {
                    ['zn', 'zs', 'za'].forEach(suffix => {
                        checkboxFields.push(`${prefix}${i}_${suffix}`);
                    });
                }
            });

        // Asignación automática a inputs que coincidan con los nombres de las claves
            // Cargar campos normales (NO sections)
['fecha', 'diagnostico', 'observaciones', 'patient_id', 'user_id'].forEach(function (field) {
    if (rowData[field] !== undefined) {
        $('[name="' + field + '"]').val(rowData[field]);
    }
});

// Cargar SECTIONS (electroterapia)
if (rowData.sections) {

    Object.keys(rowData.sections).forEach(function (sectionKey) {
        let section = rowData.sections[sectionKey];

        Object.keys(section).forEach(function (fieldKey) {

            let inputName = `sections[${sectionKey}][${fieldKey}]`;
            let input = $('[name="' + inputName + '"]');

            if (input.length) {
                input.val(section[fieldKey]);
            }
        });
    });
}

        $('#id').val(_id);
        $('#frmModal1').modal('show');
    });


    // Evento para imprimir datos sin mostrar el modal
    $(document).on('click', '.dTableView', function () {
        var rowData = dTable.row($(this).closest('tr')).data();
        if (!rowData) return;

        // Campos checkbox
        const checkboxFields = [];
        ['c', 't', 'l', 's'].forEach(prefix => {
            for (let i = 1; i <= 12; i++) {
                ['zn', 'zs', 'za'].forEach(suffix => {
                    checkboxFields.push(`${prefix}${i}_${suffix}`);
                });
            }
        });

        // Clonar modal para manipular sin afectar el original ni mostrar
        var modalClone = $('#frmModal1').clone();

        // Ocultar búsqueda paciente, mostrar solo nombre
        modalClone.find('#patientDiv').hide();
        modalClone.find('#NompatientDiv').hide();
        modalClone.find('#DatosImpresion').show();

        // Asignar datos a inputs en clone
        Object.keys(rowData).forEach(function (key) {
            var input = modalClone.find('#' + key);
            if (input.length) {
                if (checkboxFields.includes(key)) {
                    input.prop('checked', rowData[key] == 1);
                } else {
                    input.val(rowData[key]);
                }
            }
        });

        // Convertir inputs, selects y textarea a texto legible para imprimir
        modalClone.find('input, select, textarea').each(function () {
            var $el = $(this);
            // Excluir inputs tipo hidden
            if ($el.is('input[type="hidden"]')) {
                $el.remove(); // O también puedes dejarlo invisible: $el.hide();
                return; // Salir para este elemento
            }
            var text;
            if ($el.is(':checkbox')) {
                text = $el.is(':checked') ? '✔' : '✘';
            } else {
                text = $el.val() || '';
            }
            $el.replaceWith($('<span>').text(text));
        });

        // Quitar botones, alertas, instrucciones y elementos que no deben imprimirse
        modalClone.find('.modal-header, .modal-footer, .alert, button, .input-group-append').remove();
    
        // Preparar contenido HTML para la nueva ventana
        var htmlPrint = `
            <html>
            <head>
    <title>Imprimir Electroterapia </title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #000;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .info-header {
            margin-bottom: 20px;
        }
        .info-header p {
            margin: 2px 0;
            font-size: 14px;
        }
            .logo-top-right {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 80px;
            height: auto;
            opacity: 0.8; /* semitransparente para no afectar letras */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 14px;
        }
        th, td {
            border: 1px solid #333;
            padding: 6px 10px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        .section-title {
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 8px;
            font-size: 15px;
        }
        span {
            display: inline-block;
        }
    </style>
</head>
            <body>
            <h2>Electroterapia Healing Hands</h2>
           <img src="/img/Logo.png" class="logo-top-right">
                ${modalClone.find('.modal-content')[0].outerHTML}
            </body>
            </html>
        `;
    // Abrir ventana nueva y disparar impresión
    var ventana = window.open('', '', 'width=900,height=700');
    ventana.document.write(htmlPrint);
    ventana.document.close();
    ventana.focus();

    // Esperar a que cargue para imprimir y cerrar
    ventana.onload = function () {
        ventana.print();
        ventana.close();
    };
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
                var serviceUrl = "electros-create";
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
                var serviceUrl = "electros-update";
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
                var serviceUrl = "electros-delete";
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
            var serviceUrl = "get-electros"; // cambiar
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
                            title: 'Electrotherapy List' // cambiar
                        },
                        {
                            text: '<i class="fa fa-print"></i> Print',
                            className: 'btn btn-sm',
                            extend: 'print',
                            exportOptions: {
                                columns: [2, 3, 4, 5]
                            },
                            title: 'Electrotherapy List'// cambiar
                        },
                        {
                            text: '<i class="fa fa-file-excel"></i> Excel',
                            className: 'btn btn-sm',
                            extend: 'excelHtml5',
                            exportOptions: {
                                columns: [2, 3, 4, 5]
                            },
                            title: 'Electrotherapy List'// cambiar
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
                        {
                            data: 'diagnostico',
                            name: 'Diagnostico',
                            title: 'Diagnopstico'
                        },
                        {
                            data: 'observaciones',
                            name: 'Observaciones',
                            title: 'Observaciones'
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
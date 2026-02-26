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
            $("#frmModal1").modal({
            backdrop: 'static',
            keyboard: false,
            show: true
            });
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
    
    // Mostrar/ocultar paciente
    patientDiv.style.display = 'none';
    NompatientDiv.style.display = 'block';
    DatosImpresion.style.display = 'none';

    // Asignar valores a todos los campos
    Object.keys(rowData).forEach(function (key) {
        const input = $('[name="' + key + '"]');
        if (input.length) {
            if (input.is(':checkbox')) {
                // ✅ marcar si es 1, desmarcar si es 0 o null
                input.prop('checked', rowData[key] == 1);
            } else {
                input.val(rowData[key]);
            }
        }
    });

    $('#id').val(_id);
    $('#frmModal1').modal('show');
});


// Evento para imprimir datos sin mostrar el modal
$(document).on('click', '.dTableView', function () {
    var rowData = dTable.row($(this).closest('tr')).data();
    if (!rowData) return;

    // Clonar modal para manipular sin afectar el original ni mostrar
    var modalClone = $('#frmModal1').clone();

    // Ocultar búsqueda paciente, mostrar solo sección de impresión
    modalClone.find('#patientDiv').hide();
    modalClone.find('#NompatientDiv').hide();
    modalClone.find('#DatosImpresion').show();

    // Asignar valores a inputs, selects y textareas
    modalClone.find('input, select, textarea').each(function () {
        var $el = $(this);
        var name = $el.attr('name');
        if (!name) return;

        if ($el.is(':checkbox')) {
            $el.prop('checked', rowData[name] == 1);
        } else if ($el.is('select')) {
            var val = rowData[name] || '';
            $el.find('option').prop('selected', false);
            $el.find('option[value="' + val + '"]').prop('selected', true);
        } else {
            $el.val(rowData[name] || '');
        }
    });

    // Convertir a texto legible para imprimir
    modalClone.find('input, select, textarea').each(function () {
        var $el = $(this);
        if ($el.is('input[type="hidden"]')) {
            $el.remove();
            return;
        }
        var text;
        if ($el.is(':checkbox')) {
            text = $el.is(':checked') ? '✔' : '✘';
        } else if ($el.is('select')) {
            text = $el.find('option:selected').text();
        } else {
            text = $el.val() || '';
        }
        $el.replaceWith($('<span>').text(text));
    });

    // Quitar elementos que no deben imprimirse
    modalClone.find('.modal-header, .modal-footer, .alert, button, .input-group-append').remove();

    // Preparar HTML para imprimir
    // Dentro de tu función dTableView, reemplaza la sección de htmlPrint por esta versión

const tonoMuscularLabels = {
    1: "1. Hipotonía",
    2: "2. Hipertonía",
    3: "3. TM Fluctuante",
    4: "4. TM Normal"
};

    var htmlPrint = `
<html>
<head>
    <title>Imprimir Evaluación Antropometría</title>
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


    <h2>Evaluación Antropometría Healing Hands</h2>
    <img src="/img/Logo.png" class="logo-top-right">
    <div class="info-header">
        <p><strong>Fecha de evaluación:</strong> ${rowData.fecha || ''}</p>
        <p><strong>Paciente:</strong> ${rowData.customer_name || ''}</p>
        <p><strong>Encargado:</strong> ${rowData.name_user || ''}</p>
    </div>
    <div style="display: flex; justify-content: center; gap: 40px; margin-bottom: 15px; font-size: 14px;">
    <div><strong>Peso (kg):</strong> ${rowData.peso || ''}</div>
    <div><strong>Talla:</strong> ${rowData.talla || ''}</div>
</div>

    <div class="section-title">Perímetros</div>
    <table>
        <thead>
            <tr>
                <th>Perímetro</th>
                <th>Derecho</th>
                <th>Izquierdo</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>1. Brazo flexionado en máxima tensión</td><td>${rowData.brazo_flex_der || ''}</td><td>${rowData.brazo_flex_izq || ''}</td></tr>
            <tr><td>2. Brazo relajado</td><td>${rowData.brazo_rela_der || ''}</td><td>${rowData.brazo_rela_izq || ''}</td></tr>
            <tr><td>3. Antebrazo</td><td>${rowData.anteb_der || ''}</td><td>${rowData.anteb_izq || ''}</td></tr>
            <tr><td>4. Muñeca</td><td>${rowData.mu_der || ''}</td><td>${rowData.mu_izq || ''}</td></tr>
            <tr><td>5. Muslo</td><td>${rowData.mus_der || ''}</td><td>${rowData.mus_izq || ''}</td></tr>
            <tr><td>6. Pantorrilla</td><td>${rowData.pant_der || ''}</td><td>${rowData.pant_izq || ''}</td></tr>
            <tr><td>7. Tobillo</td><td>${rowData.tob_der || ''}</td><td>${rowData.tob_izq || ''}</td></tr>
            <tr><td>8. Cabeza</td><td>${rowData.cabeza_der || ''}</td><td>${rowData.cabeza_izq || ''}</td></tr>
            <tr><td>9. Cuello</td><td>${rowData.cue_der || ''}</td><td>${rowData.cue_izq || ''}</td></tr>
            <tr><td>10. Tórax</td><td>${rowData.tor_der || ''}</td><td>${rowData.tor_izq || ''}</td></tr>
            <tr><td>11. Cintura</td><td>${rowData.cint_der || ''}</td><td>${rowData.cint_izq || ''}</td></tr>
            <tr><td>12. Cadera</td><td>${rowData.cade_der || ''}</td><td>${rowData.cade_izq || ''}</td></tr>
        </tbody>
    </table>

    <div class="section-title">Observaciones</div>
    <p>${rowData.observaciones || ''}</p>

    <div class="section-title">Si presenta edema, inflamación, etc. especificar:</div>
    <p><strong>Lugar:</strong> ${rowData.lug || ''}</p>
    <p><strong>Diámetro:</strong> ${rowData.diam || ''}</p>
    <p><strong>Observaciones:</strong> ${rowData.observaciones2 || ''}</p>

    <div class="section-title">Evaluación del Tono Muscular</div>
    <p>${tonoMuscularLabels[rowData.tono_muscular] || ''}</p>
    <p><strong>Observaciones y resultados:</strong> ${rowData.observaciones_res || ''}</p>
</body>
</html>
`;


    // Abrir ventana nueva y disparar impresión
    var ventana = window.open('', '', 'width=900,height=700');
    ventana.document.write(htmlPrint);
    ventana.document.close();
    ventana.focus();
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

        // 🔹 Forzar que todos los checkboxes tengan valor 1 o 0 antes de serialize()
        $(form).find('input[type=checkbox]').each(function () {
            if (!$(this).is(':checked')) {
                $(this).prop('checked', false).val(0);
            } else {
                $(this).val(1);
            }
        });

        JsManager.StartProcessBar();
        var jsonParam = form.serialize();
        var serviceUrl = "antropoms-create";

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

        // 🔹 Igual que en Save: actualizar valores de checkboxes
        $(form).find('input[type=checkbox]').each(function () {
            if (!$(this).is(':checked')) {
                $(this).prop('checked', false).val(0);
            } else {
                $(this).val(1);
            }
        });

        JsManager.StartProcessBar();
        var jsonParam = form.serialize();
        var serviceUrl = "antropoms-update";

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
                var serviceUrl = "antropoms-delete";
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
            var serviceUrl = "get-antropoms"; // cambiar
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
                            title: 'Anthropometry List' // cambiar
                        },
                        {
                            text: '<i class="fa fa-print"></i> Print',
                            className: 'btn btn-sm',
                            extend: 'print',
                            exportOptions: {
                                columns: [2, 3, 4, 5]
                            },
                            title: 'Anthropometry List'// cambiar
                        },
                        {
                            text: '<i class="fa fa-file-excel"></i> Excel',
                            className: 'btn btn-sm',
                            extend: 'excelHtml5',
                            exportOptions: {
                                columns: [2, 3, 4, 5]
                            },
                            title: 'Anthropometry List'// cambiar
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
                            data: 'observaciones',
                            name: 'observaciones',
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
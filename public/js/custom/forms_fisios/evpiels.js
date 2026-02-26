(function ($) {
    "use strict";
    var dTable = null;
    var _id = null;

    $(document).ready(function () {
        //load datatable
        Manager.GetDataList(0);
        Manager.LoadUserDropdown();
        Manager.LoadPatientDropDown();

        //generate datatable serial no
        dTableManager.dTableSerialNumber(dTable);

        //add modal
        $("#btnAdd").on("click", function () {
            _id = null;
            patientDiv.style.display = 'block';
            NompatientDiv.style.display = 'none';
            Manager.ResetForm();
            $("#frmModal1").modal({
            backdrop: 'static',
            keyboard: false,
            show: true
            });
            calcularTotal(); // reinicia total
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

    // === 🚀 Canvas para mapeo de la silueta ===
        (function () {
            const canvas = document.getElementById("miCanvas");
            if (!canvas) return;

            const ctx = canvas.getContext("2d");
            const img = new Image();
            img.src = "/img/Evpiels.png"; // ruta relativa desde public/
            const seleccionadas = []; // zonas seleccionadas

            img.onload = () => dibujar();

            function dibujar() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                seleccionadas.forEach(zona => {
                    ctx.fillStyle = zona.color;
                    ctx.fillRect(zona.x, zona.y, zona.w, zona.h);
                });
            }

            function cargarZonasDesdeInputs() {
                seleccionadas.length = 0;
                const alto = canvas.height;
                const ancho = canvas.width;
                const figuraAncho = ancho / 2;
                const mitadFigura = figuraAncho / 2;

                const zonasConfig = {
                    "estado_izquierdo_posterior": {x: 0, y: 0, w: mitadFigura, h: alto},
                    "estado_izquierdo_anterior": {x: mitadFigura, y: 0, w: mitadFigura, h: alto},
                    "estado_derecho_posterior": {x: figuraAncho, y: 0, w: mitadFigura, h: alto},
                    "estado_derecho_anterior": {x: figuraAncho + mitadFigura, y: 0, w: mitadFigura, h: alto}
                };

                for (let id in zonasConfig) {
                    const input = document.getElementById(id);
                    if (input && input.value && input.value.trim() !== "") {
                        seleccionadas.push({
                            id: id.replace("estado_", ""),
                            ...zonasConfig[id],
                            color: "rgba(255,0,0,0.3)"
                        });
                    }
                }
                dibujar();
            }

            canvas.addEventListener("click", (e) => {
                const x = e.offsetX;
                const y = e.offsetY;
                const ancho = canvas.width;
                const alto = canvas.height;
                const figuraAncho = ancho / 2;
                const mitadFigura = figuraAncho / 2;

                let zona = null;
                if (x < figuraAncho) {
                    zona = (x < mitadFigura)
                        ? {id: "izquierdo_posterior", x: 0, y: 0, w: mitadFigura, h: alto, color: "rgba(255,0,0,0.3)"}
                        : {id: "izquierdo_anterior", x: mitadFigura, y: 0, w: mitadFigura, h: alto, color: "rgba(255,0,0,0.3)"};
                } else {
                    zona = (x < figuraAncho + mitadFigura)
                        ? {id: "derecho_posterior", x: figuraAncho, y: 0, w: mitadFigura, h: alto, color: "rgba(255,0,0,0.3)"}
                        : {id: "derecho_anterior", x: figuraAncho + mitadFigura, y: 0, w: mitadFigura, h: alto, color: "rgba(255,0,0,0.3)"};
                }

                if (!zona) return;

                const index = seleccionadas.findIndex(z => z.id === zona.id);
                if (index >= 0) {
                    // desmarcar
                    seleccionadas.splice(index, 1);
                    document.getElementById("estado_" + zona.id).value = "";
                } else {
                    if (seleccionadas.length < 2) {
                        seleccionadas.push(zona);
                        document.getElementById("estado_" + zona.id).value = "Alteración detectada";
                    } else {
                        alert("Solo puedes seleccionar 2 zonas a la vez.");
                    }
                }
                dibujar();
            });

            // función pública para recargar zonas al editar paciente
            window.recargarCanvas = cargarZonasDesdeInputs;
        })();
        // === 🚀 Fin Canvas ===

    // Show edit info modal
    $(document).on('click', '.dTableEdit', function () {
        var rowData = dTable.row($(this).parent()).data();
        console.log(rowData);
        _id = rowData.id;

        //Mostrar y ocultar nombre paciente y busqueda
        patientDiv.style.display = 'none';
        NompatientDiv.style.display = 'block';

        // Asignación automática de valores (inputs normales)
        Object.keys(rowData).forEach(key => {
            const input = $('[name="' + key + '"]');
            if (input.length) {
                input.val(rowData[key]);
            }
        });

 // 🔥 Recargar canvas con zonas guardadas
            recargarCanvas();

        $('#id').val(_id);
        $('#frmModal1').modal('show');
    });
    // Fin de Show edit info modal

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
            $('#input_total_puntaje').val(0);
            $('#total_puntaje').text('0 / 15');
            // limpiar canvas
                if (window.recargarCanvas) {
                    recargarCanvas();
                }
        },

        Save: function (form) {
            if (Message.Prompt()) {
                JsManager.StartProcessBar();
                var jsonParam = form.serialize(); // incluye el hidden
                var serviceUrl = "evpiels-create";
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
                var jsonParam = form.serialize(); // incluye el hidden
                var serviceUrl = "evpiels-update";
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
                var serviceUrl = "evpiels-delete";
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
            var serviceUrl = "get-evpiels"; 
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
                            exportOptions: { columns: [2, 3, 4, 5] },
                            title: 'Skin assessment List'
                        },
                        {
                            text: '<i class="fa fa-print"></i> Print',
                            className: 'btn btn-sm',
                            extend: 'print',
                            exportOptions: { columns: [2, 3, 4, 5] },
                            title: 'Skin assessment List'
                        },
                        {
                            text: '<i class="fa fa-file-excel"></i> Excel',
                            className: 'btn btn-sm',
                            extend: 'excelHtml5',
                            exportOptions: { columns: [2, 3, 4, 5] },
                            title: 'Skin assessment List'
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
                            orderable: false,
                            searchable: false,
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
                        { data: 'fecha', name: 'Fecha', title: 'Fecha' },
                        { data: 'customer_name', name: 'customer_name', title: 'customer name' },
                        { data: 'name_user', name: 'name_user', title: 'Encargado' },
                        { data: 'diagnostico', name: 'diagnostico', title: 'Diagnostico' },
                        { data: 'observaciones', name: 'observaciones', title: 'Observaciones' }
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

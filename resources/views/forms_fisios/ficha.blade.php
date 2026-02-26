@extends('layouts.app')
@section('content')
@push("adminScripts")
<script src="{{ dsAsset('js/lib/country-list.js') }}"></script>
<script src="{{dsAsset('js/custom/forms_fisios/ficha.js')}}"></script>
@endpush

@push("adminCss")
<link href="{{ dsAsset('js/lib/jquery-schedule-plus/css/style.css')}}" rel="stylesheet" />
<link href="{{ dsAsset('css/custom/forms_fisios/fis-ficha.css')}}" rel="stylesheet" />
@endpush
<!-- Autor: Daniel's -->

<div class="page-inner">

    <!--Modal add menu-->
    <div class="modal fade" id="frmModal1" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form class="form-horizontal" id="inputForm" novalidate="novalidate" enctype="multipart/form-data">

                    
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <span class="fw-mediumbold">
                                {{translate('Ficha Clinica')}}
                            </span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <!-- Necesrio en todos los formularios -->
                    <div class="modal-body">
                        <input type="hidden" name="id" id="id" value="0"> <!-- Necesario para carga el ID del registro a actualiza, valida si es insert o update -->
                        <div class="row" id='patientDiv'>
                            <div class="col-md-12 mt-3">
                                <div class="form-group">
                                    <label for="patient_id" class="float-left">Paciente<b class="color-red"> *</b></label>
                                    <div class="input-group">
                                        <select required id="patient_id" name="patient_id" class="form-control" data-live-search="true"></select>
                                        <div class="input-group-append">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                         <!-- Datos para impresion -->
                        <div class="row mt-3">
                            <div class="col-md-12" id='NompatientDiv'>
                                <label>Paciente</label>
                                <input type="text" id="customer_name" name="customer_name" class="form-control" readonly>
                            </div>
                            <div class="col-md-12" id='DatosImpresion'>
                                <label>Fecha de evaluación:</label>
                                <input type="text" id="fecha" name="fecha" class="form-control" readonly><br>
                                <label>Paciente:</label>
                                <input type="text" id="customer_name2" name="customer_name2" class="form-control" readonly><br>
                                <label>Edad:</label>
                                <input type="text" id="age" name="age" class="form-control" readonly><br>
                                <label>Encargado:</label>
                                <input type="text" id="encargado" name="encargado" class="form-control" readonly>
                            </div>
                        </div>
                        
                        <div class="col-md-12" id='DatosImpresion'>
                            <label>Fecha de evaluación:</label>
                            <input type="text" id="fecha" name="fecha" class="form-control" readonly><br>
                            <label>Paciente:</label>
                            <input type="text" id="customer_name2" name="customer_name2" class="form-control" readonly><br>
                            <label>Edad:</label>
                            <input type="text" id="age" name="age" class="form-control" readonly><br>
                            <label>Encargado:</label>
                            <input type="text" id="encargado" name="encargado" class="form-control" readonly>
                        </div>
                    <!-- Necesrio en todos los formularios -->

                        <div class="container">
                            <div class="alert alert-info" role="alert" >
                                <h5 class="text-center mb-3">
                               <strong>Instrucciones:</strong> 
                                <!-- Div para agregar instrucciones -->
                                <div class="d-flex flex-column flex-md-row flex-wrap justify-content-center gap-2">

                                </div>
                                <!-- Div para agregar instrucciones -->
                            </div>
                        </div>   

                        <div class="table-responsive">
                            
                         {{-- ================== DIAGNOSTICO ================== --}}
                        <div class="caja-titulo">Diagnostico</div>
                        <textarea name="diagnostico" class="form-control mb-4" rows="2" placeholder="Describa el diagnóstico..."></textarea>
                        
                        {{-- ================== MOTIVO DE CONSULTA ================== --}}
                        <div class="caja-titulo">Motivo de consulta</div>
                        <textarea name="motivo_consulta" class="form-control mb-4" rows="2" placeholder="Describa el motivo de la consulta..."></textarea>

                        {{-- ==================ANTECEDENTES MÉDICOS RELEVANTES ================== --}}
                        <div class="caja-titulo">Antecedentes médicos relevantes</div>

                        <div class="mb-3">
                            <label class="form-label">Historial médico</label>
                            <textarea name="historial_medico" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Enfermedades crónicas</label>
                            <textarea name="enfermedades_cronicas" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cirugías previas</label>
                            <textarea name="cirugias_previas" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Medicamentos actuales</label>
                            <textarea name="medicamentos_actuales" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Alergias</label>
                            <textarea name="alergias" class="form-control" rows="2"></textarea>
                        </div>

                        {{-- ================== HISTORIA DE LA LESIÓN ================== --}}
                        <h5 class="caja-titulo">Historia de la lesión o condición actual</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Fecha de inicio</label>
                                <input type="date" name="fecha_inicio" class="form-control">
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Mecanismo de lesión / origen</label>
                                <textarea name="mecanismo_lesion_origen" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Evolución de los síntomas</label>
                            <textarea name="evolucion_sintomas" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Tratamientos previos</label>
                            <textarea name="tratamientos_previos" class="form-control" rows="2"></textarea>
                        </div>

                        {{-- ================== EVALUACIÓN FISIOTERAPÉUTICA ================== --}}
                        <h5 class="caja-titulo">Evaluación fisioterapéutica</h5>

                        <p class="mb-1 fw-semibold">A. Observación:</p>
                        <div class="mb-3">
                            <label class="form-label">- Marcha</label>
                            <textarea name="observacion_marcha" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">- Otros</label>
                            <textarea name="observacion_otros" class="form-control" rows="2"></textarea>
                        </div>

                        {{-- ==================DIAGNÓSTICO FISIOTERAPÉUTICO ================== --}}
                        <h5 class="caja-titulo">Diagnóstico fisioterapéutico</h5>
                        <textarea name="diagnostico_fisioterapeutico" class="form-control mb-4" rows="3"></textarea>

                        {{-- ==================OBJETIVOS DEL TRATAMIENTO ================== --}}
                        <h5 class="caja-titulo">Objetivos del tratamiento</h5>

                        <div class="mb-3">
                            <label class="form-label">Corto plazo</label>
                            <textarea name="corto_plazo" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mediano plazo</label>
                            <textarea name="mediano_plazo" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Largo plazo</label>
                            <textarea name="largo_plazo" class="form-control" rows="2"></textarea>
                        </div>

                        {{-- ================== PLAN DE TRATAMIENTO ================== --}}
                <h5 class="caja-titulo">Plan de tratamiento</h5>
                <p class="fw-semibold">Modalidades:</p>

                @php
                    // Listas divididas en dos columnas
                    $modalidades_col1 = [
                        'modalidades_ejercicio_terapeutico' => 'Ejercicio terapéutico',
                        'modalidades_electroterapia'        => 'Electroterapia',
                        'modalidades_masoterapia'           => 'Masoterapia',
                        'modalidades_estiramientos'         => 'Estiramientos',
                    ];

                    $modalidades_col2 = [
                        'modalidades_tecaterapia'   => 'Tecarterapia',
                        'modalidades_puncion_seca'  => 'Punción seca',
                        'modalidades_electropuncion'=> 'Electropunción',
                    ];
                @endphp

                <div class="row mb-3">
                    {{-- Primera columna --}}
                    <div class="col-md-6">
                        @foreach ($modalidades_col1 as $name => $label)
                            <div class="form-check form-switch switch-wrapper">
                                <!-- Campo oculto para enviar "0" cuando no esté marcado -->
                                <input type="hidden" name="{{ $name }}" value="0">
                                <input type="checkbox" class="form-check-input" name="{{ $name }}" value="1">
                                <label class="form-check-label">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>

                    {{-- Segunda columna --}}
                    <div class="col-md-6">
                        @foreach ($modalidades_col2 as $name => $label)
                            <div class="form-check form-switch switch-wrapper">
                                <!-- Campo oculto para enviar "0" cuando no esté marcado -->
                                <input type="hidden" name="{{ $name }}" value="0">
                                <input type="checkbox" class="form-check-input" name="{{ $name }}" value="1">
                                <label class="form-check-label">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Otros tratamientos</label>
                    <textarea name="modalidades_otros" class="form-control" rows="2" placeholder="Escribir aquí los otros tratamientos..."></textarea>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Frecuencia (veces/semana)</label>
                        <input type="number" name="frecuencia_semana" class="form-control" value="1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Duración estimada (semanas)</label>
                        <input type="number" name="duracion_semanas" class="form-control" value="10">
                    </div>
                </div>

                        {{-- ==================EVOLUCIÓN / NOTAS DE SESIÓN ================== --}}
                        <!-- <h5 class="caja-titulo">Evolución / Notas de sesión</h5>
                        <p class="text-muted fst-italic">
                            (Este campo se llena cada tres tratamientos. El paciente ha mejorado en su rango de movimiento, revisar chequeo articular.)
                        </p>

                        <div class="table-responsive mb-4">
                            <table class="table table-bordered align-middle text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tratamiento realizado</th>
                                        <th>Observaciones</th>
                                        <th>Firma profesional</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><input type="date" name="fecha_tratamiento" class="form-control"></td>
                                        <td><textarea name="tratamiento_realizado" class="form-control" rows="2"></textarea></td>
                                        <td><textarea name="observaciones" class="form-control" rows="2"></textarea></td>
                                        <td><input type="text" name="firma_profesional" class="form-control"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div> -->

                        {{-- ==================ALTA Y RECOMENDACIONES ================== --}}
                        <!-- <h5 class="caja-titulo">Alta y recomendaciones</h5>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Fecha de alta</label>
                                <input type="date" name="fecha_alta" class="form-control">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Recomendaciones finales</label>
                                <textarea name="recomendaciones_finales" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Firma</label>
                            <input type="text" name="firma" class="form-control">
                        </div> -->

        
                            
                      </div>
                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">{{translate('Close')}}</button>
                        <button type="submit" class="btn btn-success btn-sm">{{translate('Save Change')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
                
                <!-- Modal Nuevo Seguimiento -->
            <div class="modal fade" id="modalSeguimiento" tabindex="-1" role="dialog" aria-labelledby="modalSeguimiento" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form id="formSeguimiento">
    @csrf
    <input type="hidden" name="seguimiento_id" id="seguimiento_id">
    <input type="hidden" name="ficha_id" id="ficha_id">
    <input type="hidden" name="patient_id" id="seguimiento_patient_id">

    <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="modalSeguimiento">Nuevo Seguimiento</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
        <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <div class="modal-body">
        <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>   
        <label>Fecha:</label>
        <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" required>

        <label>Tratamiento realizado:</label>
        <textarea name="tratamiento_realizado" class="form-control"></textarea>

        <label>Observaciones:</label>
        <textarea name="observaciones" class="form-control"></textarea>

        <label>Evolución:</label>
        <textarea name="evolucion" class="form-control"></textarea>

        <hr>
        <label>Notas detalladas (multimedia):</label>
        <textarea id="editor_detallado" name="nota_detallada" class="form-control"></textarea>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        <button type="submit" class="btn btn-success">Guardar</button>
    </div>
</form>

                    </div>
                </div>
            </div>
                <!-- End Modal Fin Seguimiento -->

                <!-- Modal Ver Seguimiento -->
                <div class="modal fade" id="modalVerSeguimiento" tabindex="-1" role="dialog" aria-labelledby="verSeguimientoLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title" id="verSeguimientoLabel">Seguimiento del Paciente</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" id="seguimientoContent">
                        <p class="text-center text-muted">Selecciona un registro para ver los seguimientos.</p>
                    </div>
                    
                    </div>
                </div>
                </div>
                <!-- End Modal Ver Seguimiento -->
                
    <!-- category datatable -->
    <div class="row">
        <div class="col-md-12">
            <div class="main-card card">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <h4 class="card-title">
                            {{translate('Ficha Clinica')}}  
                        </h4>
                        <a class="btn btn-primary btn-sm btn-round ml-auto" href="{{action([\App\Http\Controllers\FormFisios\FichaController::class, 'formFicha_form'])}}">
                        <i class="fa fa-plus"></i> {{ translate('Add New') }}
                        </a>
                        {{-- <button id="btnAdd" class="btn btn-primary btn-sm btn-round ml-auto">
                            <i class="fa fa-plus"></i> {{translate('Add New')}}
                        </button> --}}
                    </div>
                </div>
                <div class="card-body">
                    <table id="tableElement" class="table table-bordered w100"></table>
                </div>
            </div>
        </div> 
    </div>
</div>



@endsection


<script>
document.addEventListener('DOMContentLoaded', function () {
   
});
</script>
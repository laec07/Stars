@extends('layouts.app')
@section('content')
@push("adminScripts")
<script src="{{ dsAsset('js/lib/country-list.js') }}"></script>
<script src="{{dsAsset('js/custom/forms_fisios/evdolors.js')}}"></script>
@endpush

@push("adminCss")
<link href="{{ dsAsset('js/lib/jquery-schedule-plus/css/style.css')}}" rel="stylesheet" />
<link href="{{ dsAsset('css/custom/forms_fisios/fis-evdolors.css')}}" rel="stylesheet" />
@endpush
<!-- Autor: laestrada -->
<!-- Descripción: Formulario para chequo muscular -->
<div class="page-inner">

    <!--Modal add menu-->
    <div class="modal fade" id="frmModal1" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form class="form-horizontal" id="inputForm" novalidate="novalidate" enctype="multipart/form-data">

                    
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <span class="fw-mediumbold">
                                {{translate('Evaluacion del dolor')}}
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
                        <div class="row mt-3">
                            <div class="col-md-12" id='NompatientDiv'>
                                <label>Paciente</label>
                                <input type="text" id="customer_name" name="customer_name" class="form-control" readonly>
                            </div>
                        </div>
                    <!-- Necesrio en todos los formularios -->

                        <div class="container">
                            <div class="alert alert-info" role="alert" >
                                <h5 class="text-center mb-3">
                               <strong>Instrucciones:</strong> El dolor es subjetivo por lo cual se le preguntara al paciente de 1 a 10 y 
                               segùn la expresiòn facial cuanto de dolor siente y el numero que nos diga será la escala numérica de dolor, 
                               y se le seleccionará al numero que pertenezca.
                                </h5> <br>
                                <!-- Div para agregar instrucciones -->
                                <div class="d-flex flex-column flex-md-row flex-wrap justify-content-center gap-2">

                                </div>
                                <!-- Div para agregar instrucciones -->
                            </div>
                        </div>   

                        <div class="table-responsive">
                             <div class="form-group">
            <label for="pain_location">Localización del dolor:</label>
            <input type="text" name="pain_location" id="pain_location" class="form-control" maxlength="100" value="{{ old('pain_location') }}" required>
        </div>

        <div class="form-group">
            <label for="pain_start_when">¿Cuándo comenzó?</label>
            <input type="text" name="pain_start_when" id="pain_start_when" class="form-control" maxlength="100" value="{{ old('pain_start_when') }}" required>
        </div>

        <div class="form-group">
    <label for="pain_start_time">Hora en que comenzó el dolor:</label>
    <input type="time" name="pain_start_time" id="pain_start_time" class="form-control" value="{{ old('pain_start_time') }}">
</div>

<div class="form-group">
    <label for="pain_end_time">Hora en que terminó el dolor:</label>
    <input type="time" name="pain_end_time" id="pain_end_time" class="form-control" value="{{ old('pain_end_time') }}">
</div>

<div class="form-group">
    <label for="pain_severity">¿Qué gravedad tuvo el dolor? (marcar con un número):</label>
    <select name="pain_severity" id="pain_severity" class="form-control">
        <option value="">Seleccione</option>
        <option value="1" {{ old('pain_severity') == 1 ? 'selected' : '' }}>1. Dolor leve</option>
        <option value="2" {{ old('pain_severity') == 2 ? 'selected' : '' }}>2. Malestar</option>
        <option value="3" {{ old('pain_severity') == 3 ? 'selected' : '' }}>3. Dolor moderado</option>
        <option value="4" {{ old('pain_severity') == 4 ? 'selected' : '' }}>4. Dolor intenso</option>
        <option value="5" {{ old('pain_severity') == 5 ? 'selected' : '' }}>5. Dolor insoportable</option>
    </select>
</div>


        <div class="form-group">
            <label for="pain_place">¿Dónde se encontraba?</label>
            <input type="text" name="pain_place" id="pain_place" class="form-control" maxlength="100" value="{{ old('pain_place') }}">
        </div>

        <div class="form-group">
            <label for="pain_activity">¿Qué estaba haciendo?</label>
            <input type="text" name="pain_activity" id="pain_activity" class="form-control" maxlength="100" value="{{ old('pain_activity') }}">
        </div>

       <div class="form-group">
    <label for="pain_usual_intensity">¿Cuál era la intensidad habitual de su dolor? (1 mínima - 10 máxima):</label>
    <select name="pain_usual_intensity" id="pain_usual_intensity" class="form-control">
        <option value="">Seleccione</option>
        @for ($i = 1; $i <= 10; $i++)
            <option value="{{ $i }}" {{ old('pain_usual_intensity') == $i ? 'selected' : '' }}>{{ $i }}</option>
        @endfor
    </select>
</div>


        <div class="form-group">
    <label for="pain_reduction_method">¿Cómo intentó reducir el dolor?</label>
    <select name="pain_reduction_method" id="pain_reduction_method" class="form-control">
        <option value="">Seleccione</option>
        <option value="Analgesicos" {{ old('pain_reduction_method') == 'Analgesicos' ? 'selected' : '' }}>Analgesicos</option>
        <option value="Anti inflamatorios" {{ old('pain_reduction_method') == 'Anti inflamatorios' ? 'selected' : '' }}>Anti inflamatorios</option>
        <option value="Esperar" {{ old('pain_reduction_method') == 'Esperar' ? 'selected' : '' }}>Esperar</option>
        <option value="Sedante" {{ old('pain_reduction_method') == 'Sedante' ? 'selected' : '' }}>Sedante</option>
        <option value="Otros" {{ old('pain_reduction_method') == 'Otros' ? 'selected' : '' }}>Otros</option>
    </select>
</div>

        <div class="form-group">
    <label for="pain_severity">¿Qué eficacia tuvo? (número):</label>
    <select name="pain_reduction_effectiveness" id="pain_reduction_effectiveness" class="form-control">
        <option value="">Seleccione</option>
        <option value="1" {{ old('pain_reduction_effectiveness') == 0 ? 'selected' : '' }}>0. No me alivio en lo absoluto</option>
        <option value="2" {{ old('pain_reduction_effectiveness') == 1 ? 'selected' : '' }}>1. Me alivio muy poco</option>
        <option value="3" {{ old('pain_reduction_effectiveness') == 2 ? 'selected' : '' }}>2. Me alivio algo</option>
        <option value="4" {{ old('pain_reduction_effectiveness') == 3 ? 'selected' : '' }}>3. Me alivió mucho</option>
        <option value="5" {{ old('pain_reduction_effectiveness') == 4 ? 'selected' : '' }}>4. Desaparecio el dolor</option>
    </select>
</div>
                            
                      </div>


                        <div class="form-group control-group form-inline ">
                            <label>{{translate('Observaciones')}}</label>
                            <textarea type="text" id="observaciones" name="observaciones"  class="form-control input-full"></textarea>
                            <span class="help-block"></span>
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

    <!-- category datatable -->
    <div class="row">
        <div class="col-md-12">
            <div class="main-card card">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <h4 class="card-title">
                            {{translate('Dolor')}}  
                        </h4>
                        <button id="btnAdd" class="btn btn-primary btn-sm btn-round ml-auto">
                            <i class="fa fa-plus"></i> {{translate('Add New')}}
                        </button>
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
@extends('layouts.app')
@section('content')
@push("adminScripts")
<script src="{{ dsAsset('js/lib/country-list.js') }}"></script>
<script src="{{dsAsset('js/custom/forms_fisios/electros.js')}}"></script>
@endpush

@push("adminCss")
<link href="{{ dsAsset('js/lib/jquery-schedule-plus/css/style.css')}}" rel="stylesheet" />
<link href="{{ dsAsset('css/custom/forms_fisios/fis-electros.css')}}" rel="stylesheet" />
@endpush
<!-- Autor: laestrada -->
<!-- Descripción: Formulario para Electrotherapy -->
<div class="page-inner">

    <!--Modal add menu-->
    <div class="modal fade" id="frmModal1" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form class="form-horizontal" id="inputForm" novalidate="novalidate" enctype="multipart/form-data">

                    
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <span class="fw-mediumbold">
                                {{translate('Electrotherapy')}}
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
                               <strong>Instrucciones:</strong> 
                               Llenar los datos que se le piden a continuacion para llevar un control de tratamiento, 
                               de la misma manera subrayar con un resaltador de color fluorecente el nombre y punto 
                               motor a estimular con electroterapia.
                                <!-- Div para agregar instrucciones -->
                                <div class="d-flex flex-column flex-md-row flex-wrap justify-content-center gap-2">

                                </div>
                                <!-- Div para agregar instrucciones -->
                            </div>
                        </div>   

                        <div class="table-responsive">
                            

                            @php
    // Definimos las tres secciones y su tipo de corriente
    $sections = [
        [
            'title' => 'PUNTOS MOTORES DE LA CARA',
            'current_type' => 'Tens',
        ],
        [
            'title' => 'PUNTOS MOTORES MUSCULARES',
            'current_type' => 'Microcurrent',
        ],
        [
            'title' => 'PUNTOS MOTORES NERVIOSOS',
            'current_type' => 'Microcurrent',
        ],
    ];

    // Campos que se repiten en cada tabla (pares de etiqueta izquierda / derecha)
    $fields = [
        ['Waveform', 'Display'],
        ['CC/CV', 'Method'],
        ['Carrier Frecuencia', 'Channel Mode'],
        ['Frecuencia (MHz)', 'Burst Freq'],
        ['Vector Scan', 'Duty Cycle'],
        ['Treatment Time', 'Anti-Fatigue'],
        ['Cycle Time', 'Freq. Mod'],
        ['Polarity', 'Amplish. Mod.'],
        ['Ramp', 'Phase Duration'],
    ];
@endphp

@foreach ($sections as $section)
    <table class="table table-bordered text-center align-middle mb-5" 
           style="border: 2px solid #000; width: 100%; border-collapse: collapse;">
        <tbody>
            <!-- Título -->
            <tr>
                <td colspan="4" style="text-align: center; font-weight: bold; border: 2px solid #000;">
                    {{ $section['title'] }} — Tipo de Corriente:
                    <input type="text"
                           name="current_type_{{ Str::slug($section['title'], '_') }}"
                           value="{{ $section['current_type'] }}"
                           class="form-control d-inline-block"
                           style="width: 40%; display: inline-block; margin-left: 10px;">
                </td>
            </tr>

            <!-- Generar filas dinámicamente -->
            @foreach ($fields as [$left, $right])
                <tr>
                    <td style="background-color: #f3f6f9;">{{ $left }}:</td>
                    <td>
                        <input type="text"
                               name="{{ Str::slug($left, '_') }}_{{ Str::slug($section['title'], '_') }}"
                               class="form-control">
                    </td>

                    <td style="background-color: #f3f6f9;">{{ $right }}:</td>
                    <td>
                        <input type="text"
                               name="{{ Str::slug($right, '_') }}_{{ Str::slug($section['title'], '_') }}"
                               class="form-control">
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endforeach

        
                            
                      </div>
                        
                      <div class="form-group control-group form-inline ">
                                <label>Diagnostico</label>
                                <input type="text" id="diagnostico" name="diagnostico" class="form-control input-full" >
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
                            {{translate('Electrotherapy')}}  
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
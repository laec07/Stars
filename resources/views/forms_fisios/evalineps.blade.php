@extends('layouts.app')
@section('content')
@push("adminScripts")
<script src="{{ dsAsset('js/lib/country-list.js') }}"></script>
<script src="{{dsAsset('js/custom/forms_fisios/evalineps.js')}}"></script>
@endpush

@push("adminCss")
<link href="{{ dsAsset('js/lib/jquery-schedule-plus/css/style.css')}}" rel="stylesheet" />
<link href="{{ dsAsset('css/custom/forms_fisios/fis-evalineps.css')}}" rel="stylesheet" />
@endpush
<!-- Autor: Daniel's -->
<!-- Descripción: Formulario para Evaluation of postural alignment -->
<div class="page-inner">

    <!--Modal add menu-->
    <div class="modal fade" id="frmModal1" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form class="form-horizontal" id="inputForm" novalidate="novalidate" enctype="multipart/form-data">

                    
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <span class="fw-mediumbold">
                                {{translate('Evaluation of postural alignment')}} 
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
                            <!--
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
                        -->
                        </div>
                    <!-- Necesrio en todos los formularios -->

                        <div class="container">
                            <div class="alert alert-info no-print" role="alert" id="instructions">
                                <div class="d-flex flex-wrap justify-content-center gap-3 text-center">
                                    <span class="badge px-4 py-2" style="background-color: #29292b; color: #f6f8fc; border-radius: 10px;">
                                        Lateral Derecho
                                    </span>
                                    <span class="badge px-4 py-2" style="background-color: #29292b; color: #f6f8fc; border-radius: 10px;">
                                        Posterior
                                    </span>
                                    <span class="badge px-4 py-2" style="background-color: #29292b; color: #f6f8fc; border-radius: 10px;">
                                        Anterior
                                    </span>
                                    <span class="badge px-4 py-2" style="background-color: #29292b; color: #f6f8fc; border-radius: 10px;">
                                        Lado Izquierdo
                                    </span>
                                </div>

                                <h5 class="text-center mb-3">
                                <img src="{{ asset('img/EvAlineps.png') }}" alt="Evaluation of postural alignment" class="img-fluid" style="max-height: 520px;">
                                </h5> <br>
                                <!-- Div para agregar instrucciones -->

                            </div>
                        </div>   

                        <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th style="width: 25%;">Lateral Derecho</th>
                                    <th style="width: 25%;">Posterior</th>
                                    <th style="width: 25%;">Anterior</th>
                                    <th style="width: 25%;">Lado Izquierdo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(['cabeza','hombros','codos','torax','omoplatos','columna','abdomen','pelvis','muslos','rodillas','piernas','pies'] as $campo)
                                    <tr>
                                        {{-- Lateral Derecho --}}
                                        <td>
                                            <label class="form-label fw-bold">{{ ucfirst($campo) }}</label>
                                            <input type="text" name="ld_{{ $campo }}" class="form-control form-control-sm"
                                                value="{{ old('ld_'.$campo, $eval->{'ld_'.$campo} ?? '') }}">
                                        </td>
                                        
                                        {{-- Posterior --}}
                                        <td>
                                            <label class="form-label fw-bold">{{ ucfirst($campo) }}</label>
                                            <input type="text" name="po_{{ $campo }}" class="form-control form-control-sm"
                                                value="{{ old('po_'.$campo, $eval->{'po_'.$campo} ?? '') }}">
                                        </td>

                                        {{-- Anterior --}}
                                        <td>
                                            <label class="form-label fw-bold">{{ ucfirst($campo) }}</label>
                                            <input type="text" name="an_{{ $campo }}" class="form-control form-control-sm"
                                                value="{{ old('an_'.$campo, $eval->{'an_'.$campo} ?? '') }}">
                                        </td>

                                        {{-- Lado Izquierdo --}}
                                        <td>
                                            <label class="form-label fw-bold">{{ ucfirst($campo) }}</label>
                                            <input type="text" name="li_{{ $campo }}" class="form-control form-control-sm"
                                                value="{{ old('li_'.$campo, $eval->{'li_'.$campo} ?? '') }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                        <hr>

                       <h5>Imágenes</h5>
                        <div class="row">
                            @for($i=1; $i<=4; $i++)
                                <div class="col-md-3 mb-3">
                                    <label>Foto {{ $i }}</label>
                                    <input type="file" name="foto{{ $i }}" class="form-control form-control-sm" accept="image/*">

                                    @if(isset($eval) && $eval->{'foto'.$i})
                                        <input type="hidden" name="foto{{ $i }}_old" value="{{ $eval->{'foto'.$i} }}">

                                        <!-- Vista previa -->
                                        <img id="fotoPreview{{ $i }}" 
                                            src="../{{ $eval->{'foto'.$i} }}" 
                                            class="img-thumbnail mt-2" 
                                            style="max-height:100px; display:block;">
                                    @else
                                        <img id="fotoPreview{{ $i }}" 
                                            class="img-thumbnail mt-2" 
                                            style="max-height:100px; display:none;">
                                    @endif
                                </div>
                            @endfor
                        </div>

 
                        
                        <br>
                        <br>
                        <div class="form-group control-group form-inline ">
                                <label>Diagnóstico: </label>
                                <input type="text" id="diagnostico" name="diagnostico" class="form-control input-full" >
                        </div>

                        <div class="form-group control-group form-inline ">
                            <label>{{translate('Observaciones')}}: </label>
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
                            {{translate('Evaluation of postural alignment')}}  
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
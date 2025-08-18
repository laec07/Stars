@extends('layouts.app')
@section('content')
@push("adminScripts")
<script src="{{ dsAsset('js/lib/country-list.js') }}"></script>
<script src="{{dsAsset('js/custom/forms_fisios/evpiels.js')}}"></script>
@endpush

@push("adminCss")
<link href="{{ dsAsset('js/lib/jquery-schedule-plus/css/style.css')}}" rel="stylesheet" />
<link href="{{ dsAsset('css/custom/forms_fisios/fis-evpiels.css')}}" rel="stylesheet" />
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
                                {{translate('Skin assessment')}}
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
    <!-- Canvas para mapeo de la silueta -->
    <div class="alert alert-info" role="alert">
        <h5 class="text-center mb-3" style="color: #000; font-weight: bold;">
            NOTA: Al realizar el mapeo en la silueta, marcar con rojo las alteraciones de la piel
        </h5>
        <div class="d-flex justify-content-center mb-3">
            <canvas id="miCanvas" width="700" height="720" style="border:1px solid #ccc; max-width:100%;"></canvas>
        </div>
    </div>

    <!-- Inputs ocultos necesarios -->
    <input type="hidden" name="id" id="id" value="0">

    <!-- Tabla de estado de la piel -->
    <div class="table-responsive">
        <table class="table table-bordered text-center">
            <thead class="thead-dark">
                <tr>
                    <th>Hemi</th>
                    <th>Plano</th>
                    <th>Estado de la piel</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Izquierdo</td>
                    <td>Anterior</td>
                    <td>
                        <input type="text" id="estado_izquierdo_anterior" name="estado_piel[izquierdo_anterior]" class="form-control">
                    </td>
                </tr>
                <tr>
                    <td>Izquierdo</td>
                    <td>Posterior</td>
                    <td>
                        <input type="text" id="estado_izquierdo_posterior" name="estado_piel[izquierdo_posterior]" class="form-control">
                    </td>
                </tr>
                <tr>
                    <td>Derecho</td>
                    <td>Anterior</td>
                    <td>
                        <input type="text" id="estado_derecho_anterior" name="estado_piel[derecho_anterior]" class="form-control">
                    </td>
                </tr>
                <tr>
                    <td>Derecho</td>
                    <td>Posterior</td>
                    <td>
                        <input type="text" id="estado_derecho_posterior" name="estado_piel[derecho_posterior]" class="form-control">
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Observaciones -->
    <div class="form-group mt-3">
        <label for="observaciones"><b>Observaciones</b></label>
        <textarea id="observaciones" name="observaciones" class="form-control" rows="3"></textarea>
    </div>
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
                            {{translate('Skin assessment')}}  
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


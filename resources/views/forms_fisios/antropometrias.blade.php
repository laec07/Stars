@extends('layouts.app')
@section('content')
@push("adminScripts")
<script src="{{ dsAsset('js/lib/country-list.js') }}"></script>
<script src="{{dsAsset('js/custom/forms_fisios/antropometrias.js')}}"></script>
@endpush

@push("adminCss")
<link href="{{ dsAsset('js/lib/jquery-schedule-plus/css/style.css')}}" rel="stylesheet" />
<link href="{{ dsAsset('css/custom/forms_fisios/fis-antropometrias.css')}}" rel="stylesheet" />
@endpush
<!-- Autor: laestrada -->
<!-- Descripción: Formulario para Antropometria T.F. -->
<div class="page-inner">

    <!--Modal add menu-->
    <div class="modal fade" id="frmModal1" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form class="form-horizontal" id="inputForm" novalidate="novalidate" enctype="multipart/form-data">

                    
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <span class="fw-mediumbold">
                                {{translate('Antropometria T.F.')}}
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
                                <h5 class="text-center mb-3" style="border-collapse: collapse; width: 100%; color: #000; font-weight: bold;">
                               <strong>Instrucciones:</strong> Paciente sentado en silla
                                </h5> <br>
                                <!-- Div para agregar instrucciones -->
                                <div class="d-flex flex-column flex-md-row flex-wrap justify-content-center gap-2">

                                </div>
                                <!-- Div para agregar instrucciones -->
                            </div>
                        </div>   

                        <div class="table-responsive">
                        <div class="form-group">
                            <table border="1" cellpadding="5" style="border-collapse: collapse; width: 100%; color: #000; font-weight: bold;">
                            


                                    
                                    <!-- EQUILIBRIO SENTADO -->
                                    <tr class="bg-danger text-white">
                                        <th colspan="3">EQUILIBRIO SENTADO</th>
                                    </tr>
                                    <tr>
                                        <td colspan="3">
                                            <select id="equi_s" name="equi_s" class="form-control puntaje">
                                                <option value="0">0 - Se inclina o desliza en la silla</option>
                                                <option value="1">1 - Firme y seguro</option>
                                            </select>
                                        </td>
                                    </tr>   
                                    <!-- LEVANTARSE -->
                                    <tr class="bg-danger text-white">
                                        <th colspan="3">LEVANTARSE</th>
                                    </tr>
                                    <tr>
                                        <td colspan="3">
                                            <select name="lev_i" class="form-control puntaje">
                                                <option value="0">0 - Incapaz sin ayuda</option>
                                                <option value="1">1 - Con ayuda de brazos</option>
                                                <option value="2">2 - Sin ayuda</option>
                                            </select>
                                        </td>
                                    </tr>

                                    <!-- INTENTO DE LEVANTARSE -->
                                    <tr class="bg-danger text-white">
                                        <th colspan="3">INTENTO DE LEVANTARSE</th>
                                    </tr>
                                    <tr>
                                        <td colspan="3">
                                            <select name="int_i" class="form-control puntaje">
                                                <option value="0">0 - Incapaz sin ayuda</option>
                                                <option value="1">1 - Requiere más de un intento</option>
                                                <option value="2">2 - Se levanta en un intento</option>
                                            </select>
                                        </td>
                                    </tr>

                                    <!-- EQUILIBRIO INMEDIATO -->
                                    <tr class="bg-danger text-white">
                                        <th colspan="3">EQUILIBRIO INMEDIATO AL LEVANTARSE</th>
                                    </tr>
                                    <tr>
                                        <td colspan="3">
                                            <select name="equil_i" class="form-control puntaje">
                                                <option value="0">0 - Inestable</option>
                                                <option value="1">1 - Estable con soporte</option>
                                                <option value="2">2 - Estable sin soporte</option>
                                            </select>
                                        </td>
                                    </tr>

                                    <!-- EQUILIBRIO EN BIPEDESTACIÓN -->
                                    <tr class="bg-danger text-white">
                                        <th colspan="3">EQUILIBRIO EN BIPEDESTACIÓN</th>
                                    </tr>
                                    <tr>
                                        <td colspan="3">
                                            <select name="equib_i" class="form-control puntaje">
                                                <option value="0">0 - Inestable</option>
                                                <option value="1">1 - Estable con base amplia</option>
                                                <option value="2">2 - Base estrecha sin soporte</option>
                                            </select>
                                        </td>
                                    </tr>

                                    <!-- EMPUJÓN -->
                                    <tr class="bg-danger text-white">
                                        <th colspan="3">EMPUJÓN</th>
                                    </tr>
                                    <tr>
                                        <td colspan="3">
                                            <select name="em_t" class="form-control puntaje">
                                                <option value="0">0 - Tiende a caerse</option>
                                                <option value="1">1 - Se tambalea pero se mantiene</option>
                                                <option value="2">2 - Firme</option>
                                            </select>
                                        </td>
                                    </tr>

                                    <!-- OJOS CERRADOS -->
                                    <tr class="bg-danger text-white">
                                        <th colspan="3">OJOS CERRADOS</th>
                                    </tr>
                                    <tr>
                                        <td colspan="3">
                                            <select name="oj_i" class="form-control puntaje">
                                                <option value="0">0 - Inestable</option>
                                                <option value="1">1 - Estable</option>
                                            </select>
                                        </td>
                                    </tr>

                                    <!-- GIRO DE 360° -->
                                    <tr class="bg-danger text-white">
                                        <th colspan="3">GIRO DE 360°</th>
                                    </tr>
                                    <tr>
                                        <td colspan="3">
                                            <select name="gir_p" class="form-control puntaje">
                                                <option value="0">0 - Pasos discontinuos</option>
                                                <option value="1">1 - Pasos continuos</option>
                                            </select>
                                        </td>
                                    </tr>

                                    <!-- SENTARSE -->
                                    <tr class="bg-danger text-white">
                                        <th colspan="3">SENTARSE</th>
                                    </tr>
                                    <tr>
                                        <td colspan="3">
                                            <select name="se_i" class="form-control puntaje">
                                                <option value="0">0 - Inseguro</option>
                                                <option value="1">1 - Usa brazos o movimiento no suave</option>
                                                <option value="2">2 - Seguro y movimiento suave</option>
                                            </select>
                                        </td>
                                    </tr>

                                    <!-- TOTAL mostrado en pantalla -->
                                    <tr style="background-color: #930a8d; font-size: 18px; color: white;">
                                        <td colspan="2" align="left">TOTAL</td>
                                        <td id="total_puntaje">0 / 15</td>
                                    </tr>
                                    
                            </table>
                                 <!-- Campo oculto para enviar el total -->
                                    <input type="hidden" name="total_puntaje" id="input_total_puntaje" value="0">
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
                            {{translate('Antropometría T.F.')}}  
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


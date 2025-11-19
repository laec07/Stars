@extends('layouts.app')
@section('content')
@push("adminScripts")
<script src="{{ dsAsset('js/lib/country-list.js') }}"></script>
<script src="{{dsAsset('js/custom/forms_fisios/ultras.js')}}"></script>
@endpush

@push("adminCss")
<link href="{{ dsAsset('js/lib/jquery-schedule-plus/css/style.css')}}" rel="stylesheet" />
<link href="{{ dsAsset('css/custom/forms_fisios/fis-ultras.css')}}" rel="stylesheet" />
@endpush
<!-- Autor: laestrada -->
<!-- Descripción: Formulario para Ultrasonido -->
<div class="page-inner">

    <!--Modal add menu-->
    <div class="modal fade" id="frmModal1" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form class="form-horizontal" id="inputForm" novalidate="novalidate" enctype="multipart/form-data">

                    
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <span class="fw-mediumbold">
                                {{translate('Ultrasound')}}
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
                            

                             <table class="table table-bordered text-center align-middle" style="border: 2px solid #000; width: 100%; border-collapse: collapse;">
        <tbody>
            <!-- Título centrado -->
            <tr>
                <td colspan="4" style="text-align: center; font-weight: bold; border: 2px solid #000;">
                    Tipo de Corriente:
                    <input type="text" name="current_type" id="current_type" class="form-control d-inline-block" style="width: 40%; display: inline-block; margin-left: 10px;">
                </td>
            </tr>

            <!-- Filas con color suave -->
            <tr>
                <td style="background-color: #f3f6f9;">Waveform:</td>
                <td><input type="text" name="waveform" id="waveform" class="form-control"></td>
                <td style="background-color: #f3f6f9;">Display:</td>
                <td><input type="text" name="display" id="display" class="form-control"></td>
            </tr>
            <tr>
                <td style="background-color: #f3f6f9;">CC/CV:</td>
                <td><input type="text" name="cc_cv" id="cc_cv" class="form-control"></td>
                <td style="background-color: #f3f6f9;">Method:</td>
                <td><input type="text" name="method" id="method" class="form-control"></td>
            </tr>
            <tr>
                <td style="background-color: #f3f6f9;">Carrier Frecuencia:</td>
                <td><input type="text" name="carrier_frequency" id="carrier_frequency" class="form-control"></td>
                <td style="background-color: #f3f6f9;">Channel Mode:</td>
                <td><input type="text" name="channel_mode" id="channel_mode" class="form-control"></td>
            </tr>
            <tr>
                <td style="background-color: #f3f6f9;">Frecuencia (MHz):</td>
                <td><input type="text" name="frequency_mhz" id="frequency_mhz" class="form-control"></td>
                <td style="background-color: #f3f6f9;">Burst Freq:</td>
                <td><input type="text" name="burst_frequency" id="burst_frequency" class="form-control"></td>
            </tr>
            <tr>
                <td style="background-color: #f3f6f9;">Vector Scan:</td>
                <td><input type="text" name="vector_scan" id="vector_scan" class="form-control"></td>
                <td style="background-color: #f3f6f9;">Duty Cycle:</td>
                <td><input type="text" name="duty_cycle" id="duty_cycle" class="form-control"></td>
            </tr>
            <tr>
                <td style="background-color: #f3f6f9;">Treatment Time:</td>
                <td><input type="text" name="treatment_time" id="treatment_time" class="form-control"></td>
                <td style="background-color: #f3f6f9;">Anti-Fatigue:</td>
                <td><input type="text" name="anti_fatigue" id="anti_fatigue" class="form-control"></td>
            </tr>
            <tr>
                <td style="background-color: #f3f6f9;">Cycle Time:</td>
                <td><input type="text" name="cycle_time" id="cycle_time" class="form-control"></td>
                <td style="background-color: #f3f6f9;">Freq. Mod:</td>
                <td><input type="text" name="frequency_modulation" id="frequency_modulation" class="form-control"></td>
            </tr>
            <tr>  
                <td style="background-color: #f3f6f9;">Polarity:</td>
                <td><input type="text" name="polarity" id="polarity" class="form-control"></td>
                <td style="background-color: #f3f6f9;">Amplish. Mod.:</td>
                <td><input type="text" name="amplitude_modulation" id="amplitude_modulation" class="form-control"></td>
            </tr>
            <tr>
                <td style="background-color: #f3f6f9;">Ramp:</td>
                <td><input type="text" name="ramp" id="ramp" class="form-control"></td>
                <td style="background-color: #f3f6f9;">Phase Duration:</td>
                <td><input type="text" name="phase_duration" id="phase_duration" class="form-control"></td>
            </tr>
        </tbody>
    </table>

        
                            
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
                            {{translate('Ultrasound')}}  
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
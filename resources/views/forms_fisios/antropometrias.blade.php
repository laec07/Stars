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
                        <tr style="background-color: #f59b9b;">
                            <th colspan="3">EQUILIBRIO SENTADO</th>
                        </tr>
                        <tr>
                            <td>0</td>
                            <td>Se inclina o desliza en la silla</td>
                            <td><input type="checkbox" name="equi_s" value="1"></td>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td>Firme y seguro</td>
                            <td><input type="checkbox" name="equi_f" value="1"></td>
                        </tr>

                        <!-- LEVANTARSE -->
                        <tr style="background-color: #f59b9b;">
                            <th colspan="3">LEVANTARSE</th>
                        </tr>
                        <tr>
                            <td>0</td>
                            <td>Incapaz sin ayuda</td>
                            <td><input type="checkbox" name="lev_i" value="1"></td>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td>Capaz utilizando los brazos con ayuda</td>
                            <td><input type="checkbox" name="lev_c" value="1"></td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Capaz de levantar los brazos</td>
                            <td><input type="checkbox" name="lev_ca" value="1"></td>
                        </tr>

                        <!-- INTENTO DE LEVANTARSE -->
                        <tr style="background-color: #f59b9b;">
                            <th colspan="3">INTENTO DE LEVANTARSE</th>
                        </tr>
                        <tr>
                            <td>0</td>
                            <td>Incapaz sin ayuda</td>
                            <td><input type="checkbox" name="int_i" value="1"></td>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td>Capaz pero necesita más de un intento</td>
                            <td><input type="checkbox" name="int_c" value="1"></td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Capaz de levantarse con un intento</td>
                            <td><input type="checkbox" name="int_ca" value="1"></td>
                        </tr>

                        <!-- EQUILIBRIO INMEDIATO AL LEVANTARSE -->
                        <tr style="background-color: #f59b9b;">
                            <th colspan="3">EQUILIBRIO INMEDIATO AL LEVANTARSE</th>
                        </tr>
                        <tr>
                            <td>0</td>
                            <td>Inestable (se tambalea, mueve los pies, marcando balanceo del tronco)</td>
                            <td><input type="checkbox" name="equil_i" value="1"></td>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td>Estable, pero usa andador, bastón, muletas u otros objetos de soporte</td>
                            <td><input type="checkbox" name="equil_e" value="1"></td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Estable sin usar bastón u otros soportes</td>
                            <td><input type="checkbox" name="equil_es" value="1"></td>
                        </tr>

                        <!-- EQUILIBRIO EN BIPEDESTACIÓN -->
                        <tr style="background-color: #f59b9b;">
                            <th colspan="3">EQUILIBRIO EN BIPEDESTACIÓN</th>
                        </tr>
                        <tr>
                            <td>0</td>
                            <td>Inestable</td>
                            <td><input type="checkbox" name="equib_i" value="1"></td>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td>Estable con aumento del área de sustentación</td>
                            <td><input type="checkbox" name="equib_e" value="1"></td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Base de sustentación estrecha sin ningún soporte</td>
                            <td><input type="checkbox" name="equib_b" value="1"></td>
                        </tr>

                        <!-- EMPUJÓN -->
                        <tr style="background-color: #f59b9b;">
                            <th colspan="3">EMPUJÓN</th>
                        </tr>
                        <tr>
                            <td>0</td>
                            <td>Tiende a caerse</td>
                            <td><input type="checkbox" name="em_t" value="1"></td>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td>Se tambalea, se sujeta, pero se mantiene solo</td>
                            <td><input type="checkbox" name="em_s" value="1"></td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Firme</td>
                            <td><input type="checkbox" name="em_f" value="1"></td>
                        </tr>

                        <!-- OJOS CERRADOS -->
                        <tr style="background-color: #f59b9b;">
                            <th colspan="3">OJOS CERRADOS</th>
                        </tr>
                        <tr>
                            <td>0</td>
                            <td>Inestable</td>
                            <td><input type="checkbox" name="oj_i" value="1"></td>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td>Estable</td>
                            <td><input type="checkbox" name="oj_e" value="1"></td>
                        </tr>

                        <!-- GIRO DE 360° -->
                        <tr style="background-color: #f59b9b;">
                            <th colspan="3">GIRO DE 360°</th>
                        </tr>
                        <tr>
                            <td>0</td>
                            <td>Pasos discontinuos</td>
                            <td><input type="checkbox" name="gir_p" value="1"></td>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td>Pasos continuos</td>
                            <td><input type="checkbox" name="gir_pa" value="1"></td>
                        </tr>

                        <!-- SENTARSE -->
                        <tr style="background-color: #f59b9b;">
                            <th colspan="3">SENTARSE</th>
                        </tr>
                        <tr>
                            <td>0</td>
                            <td>Inseguro (calcula mal distancia, cae en la silla)</td>
                            <td><input type="checkbox" name="se_i" value="1"></td>
                        </tr>
                        <tr>
                            <td>1</td>
                            <td>Usa los brazos o no tiene un movimiento suave</td>
                            <td><input type="checkbox" name="se_u" value="1"></td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Seguro, movimiento suave</td>
                            <td><input type="checkbox" name="se_s" value="1"></td>
                        </tr>
                        </table>

                        </div>
                            </div>
                         <!--
                        <div class="form-group control-group form-inline ">
                            <label>{{translate('Observaciones')}}</label>
                            <textarea type="text" id="observaciones" name="observaciones"  class="form-control input-full"></textarea>
                            <span class="help-block"></span>
                        </div>
                        -->
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

<script>
document.addEventListener('DOMContentLoaded', function () {
   
});
</script>
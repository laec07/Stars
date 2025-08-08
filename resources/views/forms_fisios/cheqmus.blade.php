@extends('layouts.app')
@section('content')
@push("adminScripts")
<script src="{{ dsAsset('js/lib/country-list.js') }}"></script>
<script src="{{dsAsset('js/custom/forms_fisios/cheqmus.js')}}"></script>
@endpush

@push("adminCss")
<link href="{{ dsAsset('js/lib/jquery-schedule-plus/css/style.css')}}" rel="stylesheet" />
<link href="{{ dsAsset('css/custom/forms_fisios/fis-cheqmus.css')}}" rel="stylesheet" />
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
                                {{translate('Muscle check')}}
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
                                ESCALA PARA LA VALORACIÓN DE LA FUERZA MUSCULAR
                                </h5> <br>
                                <!-- Div para agregar instrucciones -->
                                <div class="d-flex flex-column flex-md-row flex-wrap justify-content-center gap-2">
                                    <span class="badge bg-danger">Nulo: 0</span>
                                    <span class="badge bg-warning text-dark">Vestigio: 1</span>
                                    <span class="badge bg-dark text-white">Deficiente: 2</span>
                                    <span class="badge bg-info text-dark">Aceptable: 3</span>
                                    <span class="badge bg-light text-dark">Bueno: 4</span>
                                    <span class="badge bg-success">Normal: 5</span>
                                </div>
                                <!-- Div para agregar instrucciones -->
                            </div>
                        </div>   

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle">
                                <thead class="table-primary text-center">
                                    <tr>
                                
                                    <th colspan="2">IZQUIERDA</th>
                                    <th></th>
                                    <th colspan="2">DERECHA</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- CUELLO -->
                                    <tr>
                                        <th rowspan="2">CUELLO</th>
                                        <td><select id="fcm_cu_df" name="fcm_cu_df" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Flexión</td>
                                        <td><select id="fcm_cu_if" name="fcm_cu_if" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td rowspan="2">CUELLO</td>
                                    </tr>
                                    <tr>
                                        <td><select id="fcm_cu_de" name="fcm_cu_de" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Extensión</td>
                                        <td><select id="fcm_cu_ie" name="fcm_cu_ie" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                    </tr>
                                    
                                    <!-- TRONCO -->
                                    <tr>
                                        <th rowspan="3">TRONCO</th>
                                        <td><select id="fcm_tr_df" name="fcm_tr_df" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Flexión</td>
                                        <td><select id="fcm_tr_if" name="fcm_tr_if" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td rowspan="3">TRONCO</td>
                                    </tr>
                                    <tr>
                                        <td><select id="fcm_tr_de" name="fcm_tr_de" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Extensión</td>
                                        <td><select id="fcm_tr_ie" name="fcm_tr_ie" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                    </tr>
                                    <tr>
                                        <td><select id="fcm_tr_dr" name="fcm_tr_dr" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Rotación</td>
                                        <td><select id="fcm_tr_ir" name="fcm_tr_ir" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                    </tr>
                                    
                                    <!-- CADERA -->
                                    <tr>
                                        <th rowspan="6">CADERA</th>
                                        <td><select id="fcm_ca_ef" name="fcm_ca_ef" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Flexión</td>
                                        <td><select id="fcm_ca_if" name="fcm_ca_if" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td rowspan="6">CADERA</td>
                                    </tr>
                                    <tr>
                                        <td><select id="fcm_ca_de" name="fcm_ca_de" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Extensión</td>
                                        <td><select id="fcm_ca_ie" name="fcm_ca_ie" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                    </tr>
                                    <tr>
                                        <td><select id="fcm_ca_da" name="fcm_ca_da" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Abducción</td>
                                        <td><select id="fcm_ca_ia" name="fcm_ca_ia" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                    </tr>
                                    <tr>
                                        <td><select id="fcm_ca_dn" name="fcm_ca_dn" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Aducción</td>
                                        <td><select id="fcm_ca_in" name="fcm_ca_in" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                    </tr>
                                    <tr>
                                        <td><select id="fcm_ca_dr" name="fcm_ca_dr" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Rotación Interna</td>
                                        <td><select id="fcm_ca_ir" name="fcm_ca_ir" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                    </tr>
                                    <tr>
                                        <td><select id="fcm_ca_dx" name="fcm_ca_dx" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Rotación Externa</td>
                                        <td><select id="fcm_ca_ix" name="fcm_ca_ix" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                    </tr>
                                    
                                    <!-- RODILLA -->
                                    <tr>
                                        <th rowspan="2">RODILLA</th>
                                        <td><select id="fcm_ro_df" name="fcm_ro_df" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Flexión</td>
                                        <td><select id="fcm_ro_if" name="fcm_ro_if" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td rowspan="2">RODILLA</td>
                                    </tr>
                                    <tr>
                                        <td><select id="fcm_ro_dx" name="fcm_ro_dx" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Extensión</td>
                                        <td><select id="fcm_ro_ix" name="fcm_ro_ix" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                    </tr>
                                    
                                    <!-- TOBILLO -->
                                    <tr>
                                        <th rowspan="4">TOBILLO</th>
                                        <td><select id="fcm_to_di" name="fcm_to_di" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Inversión</td>
                                        
                                        <td><select id="fcm_to_ii" name="fcm_to_ii" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td rowspan="4">TOBILLO</td>
                                    </tr>
                                    <tr>
                                        <td><select id="fcm_to_de" name="fcm_to_de" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Eversión</td>
                                        <td><select id="fcm_to_ie" name="fcm_to_ie" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                    </tr>
                                    <tr>
                                        <td><select id="fcm_to_df" name="fcm_to_df" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Flexión Plantar</td>
                                        <td><select id="fcm_to_if" name="fcm_to_if" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                    </tr>
                                    <tr>
                                        <td><select id="fcm_to_dd" name="fcm_to_dd" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Flexión Dorsal</td>
                                        <td><select id="fcm_to_id" name="fcm_to_id" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                    </tr>

                                    <!-- ESCAPULA -->
                                    <tr>
                                        <th rowspan="4">ESCAPULA</th>
                                        <td><select id="fcm_es_de" name="fcm_es_de" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Elevación</td>
                                        
                                        <td><select id="fcm_es_ie" name="fcm_es_ie" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td rowspan="4">ESCAPULA</td>
                                    </tr>
                                    <tr>
                                        <td><select id="fcm_es_dd" name="fcm_es_dd" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Depresión</td>
                                        <td><select id="fcm_es_id" name="fcm_es_id" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                    </tr>
                                    <tr>
                                        <td><select id="fcm_es_da" name="fcm_es_da" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Abducción</td>
                                        <td><select id="fcm_es_ia" name="fcm_es_ia" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                    </tr>
                                    <tr>
                                        <td><select id="fcm_es_dc" name="fcm_es_dc" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Aducción</td>
                                        <td><select id="fcm_es_ic" name="fcm_es_ic" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                    </tr>

                                    <!-- HOMBRO -->
                                    <tr>
                                        <th rowspan="6">HOMBRO</th>
                                        <td><select id="fcm_ho_df" name="fcm_ho_df" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Flexión</td>
                                        <td><select id="fcm_ho_if" name="fcm_ho_if" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td rowspan="6">HOMBRO</td>
                                    </tr>
                                    <tr>
                                        <td><select id="fcm_ho_de" name="fcm_ho_de" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Extensión</td>
                                        <td><select id="fcm_ho_ie" name="fcm_ho_ie" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                    </tr>
                                    <tr>
                                        <td><select id="fcm_ho_da" name="fcm_ho_da" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Abducción</td>
                                        <td><select id="fcm_ho_ia" name="fcm_ho_ia" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                    </tr>
                                    <tr>
                                        <td><select id="fcm_ho_dc" name="fcm_ho_dc" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Aducción</td>
                                        <td><select id="fcm_ho_ic" name="fcm_ho_ic" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                    </tr>
                                    <tr>
                                        <td><select id="fcm_ho_dr" name="fcm_ho_dr" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Rotación Interna</td>
                                        <td><select id="fcm_ho_ir" name="fcm_ho_ir" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                    </tr>
                                    <tr>
                                        <td><select id="fcm_ho_dx" name="fcm_ho_dx" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Rotación Externa</td>
                                        <td><select id="fcm_ho_ix" name="fcm_ho_ix" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                    </tr>

                                    <!-- CODO -->
                                    <tr>
                                        <th rowspan="2">CODO</th>
                                        <td><select id="fcm_co_df" name="fcm_co_df" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Flexión</td>
                                        <td><select id="fcm_co_if" name="fcm_co_if" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td rowspan="2">CODO</td>
                                    </tr>
                                    <tr>
                                        <td><select id="fcm_co_de" name="fcm_co_de" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Extensión</td>
                                        <td><select id="fcm_co_ie" name="fcm_co_ie" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                    </tr>

                                    <!-- ANTEBRAZO -->
                                    <tr>
                                        <th rowspan="2">ANTEBRAZO</th>
                                        <td><select id="fcm_an_da" name="fcm_an_da" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Pronación</td>
                                        <td><select id="fcm_an_ia" name="fcm_an_ia" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td rowspan="2">ANTEBRAZO</td>
                                    </tr>
                                    <tr>
                                        <td><select id="fcm_an_ds" name="fcm_an_ds" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Supinación</td>
                                        <td><select id="fcm_an_is" name="fcm_an_is" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                    </tr>

                                    <!-- MUÑECA -->
                                    <tr>
                                        <th rowspan="2">MUÑECA</th>
                                        <td><select id="fcm_mu_dm" name="fcm_mu_dm" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Flexión</td>
                                        <td><select id="fcm_mu_im" name="fcm_mu_im" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td rowspan="2">MUÑECA</td>
                                    </tr>
                                    <tr>
                                        <td><select id="fcm_mu_de" name="fcm_mu_de" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                        <td>Extensión</td>
                                        <td><select id="fcm_mu_ie" name="fcm_mu_ie" class="form-select form-select-sm">
                                            <option selected>5</option><option>4</option><option>3</option><option>2</option><option>1</option><option>0</option>
                                        </select></td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>

                        <div class="form-group control-group form-inline ">
                                <label>Diagnostico</label>
                                <input type="text" id="Diagnostico" name="Diagnostico" class="form-control input-full" >
                        </div>

                        <div class="form-group control-group form-inline ">
                            <label>{{translate('Observaciones')}}</label>
                            <textarea type="text" id="Observaciones" name="Observaciones"  class="form-control input-full"></textarea>
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
                            {{translate('Muscle Check')}}  
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
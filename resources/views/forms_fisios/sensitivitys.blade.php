@extends('layouts.app')
@section('content')
@push("adminScripts")
<script src="{{ dsAsset('js/lib/country-list.js') }}"></script>
<script src="{{dsAsset('js/custom/forms_fisios/sensitivitys.js')}}"></script>
@endpush

@push("adminCss")
<link href="{{ dsAsset('js/lib/jquery-schedule-plus/css/style.css')}}" rel="stylesheet" />
<link href="{{ dsAsset('css/custom/forms_fisios/fis-sensitivitys.css')}}" rel="stylesheet" />
@endpush
<!-- Autor: Daniel's -->
<!-- Descripción: Formulario para Evaluacion de sensibilidad -->
<div class="page-inner">

    <!--Modal add menu-->
    <div class="modal fade" id="frmModal1" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form class="form-horizontal" id="inputForm" novalidate="novalidate" enctype="multipart/form-data">

                    
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <span class="fw-mediumbold">
                                {{translate('Sensitivity evaluation')}}
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
                                     INSTRUCCIONES: 
                                Marcar las áreas en cada categoría (Normales, Sin Sensibilidad, Con Alteraciones)
                                <img src="{{ asset('img/FisSensitivitys.png') }}" alt="Sensitivity evaluation" class="img-fluid" style="max-height: 520px;">
                                </h5> <br>
                                <!-- Div para agregar instrucciones -->
                                <div class="d-flex flex-column flex-md-row flex-wrap justify-content-center gap-2">
                                    <span class="badge" style="background-color: #9ac705; color: black; padding: 10px 15px; border-radius: 10px;">
                                        Cervical Spine Vertebrae
                                    </span>
                                    <span class="badge" style="background-color: #FFC0CB; color: black; padding: 10px 15px; border-radius: 10px;">
                                        Thoracic Spine Vertebrae
                                    </span>
                                    <span class="badge" style="background-color: #17C1E8; color: black; padding: 10px 15px; border-radius: 10px;">
                                        Lumbar Spine Vertebrae
                                    </span>
                                    <span class="badge" style="background-color: #fbc205; color: black; padding: 10px 15px; border-radius: 10px;">
                                    Sacrum Vertebrae
                                </span>

                                </div>
                                <!-- Div para agregar instrucciones -->
                            </div>
                        </div>   

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle">
                            <thead class="table-primary text-center">
                                <tr>
                                    <th></th>
                                    <th>Zonas Normales</th>
                                    <th>Zonas Sin Sensibilidad</th>
                                    <th>Zonas Con Alteraciones</th>
                                </tr>
                            </thead>
                            <!--Parte del checkbox "C"-->
                            <tbody>
                                @for ($i = 1; $i <= 8; $i++)
                                    <tr>
                                        <th class="text-center align-middle" style="background-color: #9ac705;">C{{ $i }}</th>
                                        
                                        <!-- Zonas Normales -->
                                        <td class="text-center align-middle" style="background-color: #9ac705;" >
                                            <input type="hidden" name="c{{ $i }}_zn" value="0">
                                            <input type="checkbox" class="form-check-input" name="c{{ $i }}_zn" value="1" id="c{{ $i }}_zn">
                                        </td>

                                        <!-- Zonas Sin Sensibilidad -->
                                        <td class="text-center align-middle" style="background-color: #9ac705;">
                                            <input type="hidden" name="c{{ $i }}_zs" value="0">
                                            <input type="checkbox" class="form-check-input" name="c{{ $i }}_zs" value="1" id="c{{ $i }}_zs">
                                        </td>

                                        <!-- Zonas Con Alteraciones -->
                                        <td class="text-center align-middle" style="background-color: #9ac705;">
                                            <input type="hidden" name="c{{ $i }}_za" value="0">
                                            <input type="checkbox" class="form-check-input" name="c{{ $i }}_za" value="1" id="c{{ $i }}_za">
                                        </td>

                                    </tr>
                                @endfor
                            </tbody>

                            <!--Parte del checkbox "T"-->

                            <tbody>
                                @for ($i = 1; $i <= 12; $i++)
                                    <tr>
                                        <th class="text-center align-middle" style="background-color: #FFC0CB;" >T{{ $i }}</th>

                                        <!-- Zonas Normales -->
                                        <td class="text-center align-middle" style="background-color: #FFC0CB;" >
                                            <input type="hidden" name="t{{ $i }}_zn" value="0">
                                            <input type="checkbox" class="form-check-input" name="t{{ $i }}_zn" value="1" id="t{{ $i }}_zn">
                                        </td>

                                        <!-- Zonas Sin Sensibilidad -->
                                        <td class="text-center align-middle" style="background-color: #FFC0CB;" >
                                            <input type="hidden" name="t{{ $i }}_zs" value="0">
                                            <input type="checkbox" class="form-check-input" name="t{{ $i }}_zs" value="1" id="t{{ $i }}_zs">
                                        </td>

                                        <!-- Zonas Con Alteraciones -->
                                        <td class="text-center align-middle" style="background-color: #FFC0CB;" >
                                            <input type="hidden" name="t{{ $i }}_za" value="0">
                                            <input type="checkbox" class="form-check-input" name="t{{ $i }}_za" value="1" id="t{{ $i }}_za">
                                        </td>

                                    </tr>
                                @endfor
                            </tbody>

                            <!--Parte del checkbox "L"-->

                            <tbody>
                                @for ($i = 1; $i <= 4; $i++)
                                    <tr>
                                        <th class="text-center align-middle" style="background-color: #17C1E8;">L{{ $i }}</th>

                                        <!-- Zonas Normales -->
                                        <td class="text-center align-middle" style="background-color: #17C1E8;">
                                            <input type="hidden" name="l{{ $i }}_zn" value="0">
                                            <input type="checkbox" class="form-check-input" name="l{{ $i }}_zn" value="1" id="l{{ $i }}_zn">
                                        </td>

                                        <!-- Zonas Sin Sensibilidad -->
                                        <td class="text-center align-middle" style="background-color: #17C1E8;">
                                            <input type="hidden" name="l{{ $i }}_zs" value="0">
                                            <input type="checkbox" class="form-check-input" name="l{{ $i }}_zs" value="1" id="l{{ $i }}_zs">
                                        </td>

                                        <!-- Zonas Con Alteraciones -->
                                        <td class="text-center align-middle" style="background-color: #17C1E8;">
                                            <input type="hidden" name="l{{ $i }}_za" value="0">
                                            <input type="checkbox" class="form-check-input" name="l{{ $i }}_za" value="1" id="l{{ $i }}_za">
                                        </td>

                                    </tr>
                                @endfor
                            </tbody>

                            <!--Parte del checkbox "S"-->

                           <tbody>
                                @for ($i = 1; $i <= 5; $i++)
                                    <tr>
                                        <th class="text-center align-middle" style="background-color: #fbc205;">S{{ $i }}</th>

                                        <!-- Zonas Normales -->
                                        <td class="text-center align-middle" style="background-color: #fbc205;">
                                            <input type="hidden" name="s{{ $i }}_zn" value="0">
                                            <input type="checkbox" class="form-check-input" name="s{{ $i }}_zn" value="1" id="s{{ $i }}_zn">
                                        </td>

                                        <!-- Zonas Sin Sensibilidad -->
                                        <td class="text-center align-middle" style="background-color: #fbc205;">
                                            <input type="hidden" name="s{{ $i }}_zs" value="0">
                                            <input type="checkbox" class="form-check-input" name="s{{ $i }}_zs" value="1" id="s{{ $i }}_zs">
                                        </td>

                                        <!-- Zonas Con Alteraciones -->
                                        <td class="text-center align-middle" style="background-color: #fbc205;">
                                            <input type="hidden" name="s{{ $i }}_za" value="0">
                                            <input type="checkbox" class="form-check-input" name="s{{ $i }}_za" value="1" id="s{{ $i }}_za">
                                        </td>

                                    </tr>
                                @endfor
                            </tbody>
                            <!--Fin del la Parte del checkbox -->
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
                            {{translate('Sensitivity evaluation')}}  
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
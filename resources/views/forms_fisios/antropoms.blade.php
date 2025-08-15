@extends('layouts.app')
@section('content')
@push("adminScripts")
<script src="{{ dsAsset('js/lib/country-list.js') }}"></script>
<script src="{{dsAsset('js/custom/forms_fisios/antropoms.js')}}"></script>
@endpush

@push("adminCss")
<link href="{{ dsAsset('js/lib/jquery-schedule-plus/css/style.css')}}" rel="stylesheet" />
<link href="{{ dsAsset('css/custom/forms_fisios/fis-antropoms.css')}}" rel="stylesheet" />
@endpush
<!-- Autor: Daniel's -->
<!-- Descripción: Formulario para Evaluacion de Antropometria  -->
<div class="page-inner">

    <!--Modal add menu-->
    <div class="modal fade" id="frmModal1" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form class="form-horizontal" id="inputForm" novalidate="novalidate" enctype="multipart/form-data">

                    
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <span class="fw-mediumbold">
                                {{translate('Anthropometry')}}
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
                            <div class="alert alert-info text-center" role="alert">
        <img src="{{ asset('img/antropometri.png') }}" alt="Anthropometry" 
             class="img-fluid" style="max-height: 520px;">
                  <!-- Div para agregar instrucciones -->
                
                            </div>
                        </div>   

                            {{-- Peso y Talla --}}
                    <div class="row mb-3 justify-content-center">
                        <div class="col-md-3 text-center">
                            <label class="d-block">Peso (kg)</label>
                            <input type="number" step="0.01" name="peso" class="form-control text-center"
                                value="{{ old('peso', isset($registro) ? $registro->peso : '') }}">
                        </div>
                        <div class="col-md-3 text-center">
                            <label class="d-block">Talla</label>
                            <input type="number" step="0.01" name="talla" class="form-control text-center"
                                value="{{ old('talla', isset($registro) ? $registro->talla : '') }}">
                        </div>
                    </div>

                    {{-- Perímetros --}}
                    <h5 class="mt-4">Perímetros</h5>
                    <table class="table table-bordered">
                        <thead class="table-primary text-center">
                            <tr>
                                <th>Perímetro</th>
                                <th>Derecho</th>
                                <th>Izquierdo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $perimetros = [
                                    'brazo_flex' => '1. Brazo flexionado en máxima tensión',
                                    'brazo_rela' => '2. Brazo relajado',
                                    'anteb'      => '3. Antebrazo',
                                    'mu'         => '4. Muñeca',
                                    'mus'        => '5. Muslo',
                                    'pant'       => '6. Pantorrilla',
                                    'tob'        => '7. Tobillo',
                                    'cabeza'     => '8. Cabeza',
                                    'cue'        => '9. Cuello',
                                    'tor'        => '10. Tórax',
                                    'cint'       => '11. Cintura',
                                    'cade'       => '12. Cadera',
                                ];
                            @endphp

                            @foreach($perimetros as $campo => $label)
                                @php
                                    $campo_der = $campo . '_der';
                                    $campo_izq = $campo . '_izq';
                                @endphp
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td>
                                        <input type="text" name="{{ $campo_der }}" class="form-control text-center"
                                            value="{{ old($campo_der, isset($registro) ? $registro->$campo_der : '') }}">
                                    </td>
                                    <td>
                                        <input type="text" name="{{ $campo_izq }}" class="form-control text-center"
                                            value="{{ old($campo_izq, isset($registro) ? $registro->$campo_izq : '') }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- Observaciones --}}
                    <div class="mb-3">
                        <label>Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="3">{{ old('observaciones', isset($registro) ? $registro->observaciones : '') }}</textarea>
                    </div>

                    {{-- Edema / inflamación --}}
                    <h5>Si presenta edema, inflamación, etc. especificar:</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Lugar</label>
                            <input type="text" name="lug" class="form-control"
                                value="{{ old('lug', isset($registro) ? $registro->lug : '') }}">
                        </div>
                        <div class="col-md-6">
                            <label>Diámetro</label>
                            <input type="text" name="diam" class="form-control"
                                value="{{ old('diam', isset($registro) ? $registro->diam : '') }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Observaciones</label>
                        <textarea name="observaciones2" class="form-control" rows="3">{{ old('observaciones2', isset($registro) ? $registro->observaciones2 : '') }}</textarea>
                    </div>

                    {{-- Evaluación del tono muscular --}}
                    <h5>EVALUACIÓN DEL TONO MUSCULAR</h5>
                    <p>Observar qué posiciones adopta el paciente en camilla o en la colchoneta.</p>

                    @php
                        $checkboxes = [
                            'hipo' => 'Hipotonía',
                            'hipe' => 'Hipertonía',
                            'fluc' => 'TM Fluctuante',
                            'tm_n' => 'TM Normal'
                        ];
                    @endphp

                    <div class="mb-3 text-center">
                        @foreach($checkboxes as $name => $label)
                            <label class="me-3">
                                <input type="hidden" name="{{ $name }}" value="0">
                                <input type="checkbox" 
                                    name="{{ $name }}" 
                                    value="1" 
                                    {{ old($name, isset($registro) ? $registro->$name : 0) ? 'checked' : '' }}>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>

                    <div class="mb-3">
                        <label>Observaciones y resultados</label>
                        <textarea name="observaciones_res" class="form-control" rows="3">{{ old('observaciones_res', isset($registro) ? $registro->observaciones_res : '') }}</textarea>
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
                            {{translate('Anthropometry')}}  
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
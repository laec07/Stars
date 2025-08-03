@extends('layouts.app')
@section('content')
@push("adminScripts")
<script src="{{ dsAsset('js/lib/country-list.js') }}"></script>
<script src="{{dsAsset('js/custom/patient/patient.js')}}"></script>
@endpush    

<div class="page-inner">

    <!--Modal add menu-->
    <div class="modal fade" id="frmModal" tabindex="-1" role="dialog" aria-hidden="true"> 
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content"> 
                 <form class="form-horizontal" id="inputForm" novalidate="novalidate" enctype="multipart/form-data">

                    <div class="modal-header">
                        <h5 class="modal-title">
                            <span class="fw-mediumbold">
                                {{translate('Patient Info')}} 
                            </span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <!--Empiezan los campos-->
                    <div class="modal-body">
                        <div class="container-fluid">

                            <div class="row">
                                <div class="col-md-7">
                                    <div class="form-group control-group form-inline controls">
                                        <label>{{translate('Patient Name')}} *</label>
                                        <input type="text" id="full_name" name="full_name" placeholder="{{translate('Full name')}}" required data-validation-required-message="Patient name is required" class="form-control input-full" />
                                        <span class="help-block"></span>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <label>{{ translate('System User') }}</label>
                                    <div class="form-control input-full" style="background-color: #e9ecef; pointer-events: none;">Patient</div>
                                    <input type="text" name="user_id" id="user_id" value="0">
                                    <span class="help-block"></span>
                                </div>
                            </div>

                            <div class="form-group control-group form-inline controls">
                                <label>Patient Email *</label>
                                <input type="text" id="email" name="email" placeholder="email@example.com" required data-validation-required-message="Email address is required" class="form-control input-full" />
                                <span class="help-block"></span>
                            </div>

                            <div class="row">
                                <div class="col-md-7">
                                    <div class="form-group control-group form-inline controls">

                                        <label class="col-md-12 p-0">{{translate('Patient Phone')}} *</label>
                                        <input type="tel" id="phone_no" maxlength="20" name="phone_no" placeholder="{{translate('Phone Number')}}" required data-validation-required-message="Phone number is required" class="form-control input-full w-100" />
                                        <span class="help-block"></span>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group control-group form-inline controls">
                                        <label>{{translate('Date of Birth')}} </label>
                                        <input type="text" id="dob" name="dob" class="form-control input-full datePicker" />
                                        <span class="help-block"></span>
                                    </div>
                                </div>
                            </div>
                        <!-- Empiezan los campos Agregados -->
                        
                        <div class="row mt-3 g-3">  
                            <!-- Opción Tratamiento Médico -->
                            <div class="col-md-6 col-sm-12">
                                <div class="form-group control-group controls">
                                    <label>{{ translate('He has been treated by a doctor') }}</label>
                                    <select name="treated" id="treated" class="form-control">
                                        <option value="" disabled selected>Select Option</option>
                                        <option value="Yes">Yes</option>
                                        <option value="No">No</option>
                                        <option value="Maybe">Maybe</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Pregunta: ¿Tienes algún estudio? -->
                            <div class="col-md-6 col-sm-12">
                            <div class="form-group control-group controls">
                                    <label for="has_study">{{ translate('Do you have any studies?') }}</label>
                                    <select id="has_study" name="has_study" class="form-control">
                                        <option value="" disabled selected>Select Option</option>
                                        <option value="No">{{ translate('No') }}</option>
                                        <option value="Yes">{{ translate('Yes') }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Subida de Archivos: solo si tiene estudios -->
                            <div class="col-md-6 col-sm-12" id="study_document_group" >
                                <div class="form-group control-group form-inline">
                                    <img id="empimagepreview" width="100%" />
                                    <span class="float-left w-100">{{translate('Image')}} </span>
                                    <input class="mt-1" type="file" id="image_url" name="image_url" >
                                </div>
                            </div>


                        </div>

                    </div>
                    </div>        
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">{{translate('Close')}}</button>
                        <button type="submit" id="btnSavePatient" class="btn btn-success btn-sm">{{translate('Save Patient')}}</button>
                        
                    </div>

                </form> 
            </div>
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
                            {{translate('Patient Information')}}  
                        </h4>
                        <button id="btnAdd" class="btn btn-primary btn-sm btn-round ml-auto">
                            <i class="fa fa-plus"></i> {{translate('Add New Patient')}}
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
@extends('layouts.app')
@section('content')
@push("adminScripts")
<script src="{{ dsAsset('js/lib/country-list.js') }}"></script>
<script src="{{dsAsset('js/custom/customer/customer.js')}}"></script>
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
                                {{translate('Customer Info')}} 
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
                                    <label>{{translate('Customer Name')}} *</label>
                                    <input type="text" id="full_name" name="full_name" placeholder="{{translate('Full name')}}" required data-validation-required-message="Customer name is required" class="form-control input-full" />
                                    <span class="help-block"></span>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group control-group form-inline controls"  >
                                    <label>{{translate('System User')}}</label>
                                    <div class="form-control input-full" style="background-color: #e9ecef; pointer-events: none;">Customer</div>
                                <input type="hidden" name="user_id" id="user_id" value="0">
                                    <span class="help-block"></span>
                                </div>
                            </div>


                        </div>

                        <div class="form-group control-group form-inline controls">
                            <label>Customer Email *</label>
                            <input type="email" id="email" name="email" placeholder="email@example.com" required data-validation-required-message="Email address is required" class="form-control input-full" />
                            <span class="help-block"></span>
                        </div>

                        <div class="row">
                            <div class="col-md-7">
                                <div class="form-group control-group form-inline controls">

                                    <label class="col-md-12 p-0">{{translate('Customer Phone')}} *</label>
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

                        <div class="form-group control-group form-inline ">
                            <label>{{translate('Street Address')}} *</label>
                            <textarea type="text" id="street_address" name="street_address" required data-validation-required-message="Street address is required" class="form-control input-full"></textarea>
                            <span class="help-block"></span>
                        </div>
                        <!-- Empiezan los campos Agregados -->
                        
                        <div class = "row mt-3">
                            <div class="col-md-6 col-sm-12">
                                <div class="form-group control-group controls">
                                    <label>{{translate('Occupation / Profesión')}} </label>
                                    <input type="text" id="Occupation" name="Occupation" class="form-control input-full" />
                                    <span class="help-block"></span>
                                </div>
                            </div>
                            <div class="col-md-6 col-sm-12 ">
                                <div class="control-group controls">
                                    <label>{{translate('Do you exercie? if so, what kind?// ¿usted hace ejercicio? ¿Qué tipo?:')}} </label>
                                    <input type="text" id="exercie" name="exercie" class="form-control input-full" />
                                    <span class="help-block"></span>
                                </div>
                            </div>
                        </div>
                    
                        <div class = "row mt-3">    
                            <div class="col-md-6 col-sm-12">
                            <div class="form-group control-group controls">
                                <label>{{translate('What are your hobbies? // ¿Cuáles son sus hábitos?')}} </label>
                                <input type="text" id="hobbies" name="hobbies" class="form-control input-full" />
                                <span class="help-block"></span>
                            </div>
                            </div>   
                            <div class="col-md-6 col-sm-12" >
                                <div class="control-group controls">
                                    <label>{{translate('How did you find out about our services?// ¿Cómo se enteró de nuestros servicios?')}} </label>
                                    <input type="text" id="services" name="services" class="form-control input-full" />
                                    <span class="help-block"></span>
                                </div>  
                            </div>
                        </div>   
                        <div class = "row mt-3">    
                        <div class="col-md-12 col-sm-12" >
                                <div class="control-group controls">
                                    <label>{{translate('Have you ever received massage or bodywork before? If yes, when was your last session? // Ha recibido masaje anteriormente? En caso afirmativo, ¿Cuándo fue la última sesión?')}} </label>
                                    <input type="text" id="ser" name="ser" class="form-control input-full"/>
                                    <span class="help-block"></span>
                                </div>   
                        </div>    
                        </div>
                        <div class = "row mt-3"> 
                        <div class="col-md-12 col-sm-12" >
                                <div class="control-group controls">
                                    <label>{{translate('What do you hope to experience/receive from your sesión today? (please be specific)// ¿Qué espera experimentar/ recibir de su sesión de hoy? (Por favor sea específico).')}} </label>
                                    <input type="text" id="ses" name="ses" class="form-control input-full" />
                                    <span class="help-block"></span>
                            </div>
                        </div>
                        </div>
                        <div class = "row mt-3"> 
                        <div class="col-md-12 col-sm-12" >
                            <div class="control-group controls">
                                <label>{{translate('Do you have any significant medical or physical conditions?// ¿Tiene alguna complicación médica o física que debemos saber?')}} </label>
                                <input type="text" id="medical" name="medical" class="form-control input-full" />
                                <span class="help-block"></span>
                            </div>
                        </div>
                        </div>
                        <div class = "row mt-3">
                            <div class="col-md-12 col-sm-12">
                            <label>{{translate('Do you have pain related to traumatic experience? (car accident, sports injury, surgery)/ ¿Tiene dolor relacionado con una experiencia traumática? (accidente de tráfico, lesiones deportivas, cirugía) Y/N')}}</label>
                            </div>
                        <div class="col-md-5 col-sm-12">
                            <div class="form-group control-group controls">
                               
                                <select name="traumatic" id="traumatic" class="form-control input-full">
                                    <option value="" disabled selected>Selec Option</option>
                                    <option value="YES">Yes</option>
                                    <option value="NO">No</option>
                                </select>
                                
                                <span class="help-block"></span>
                            </div>
                        </div>
                        </div>
                        <div class="form-group control-group controls">
                            <label>{{translate('If yes, briefly explain (what and when)/ En caso afirmativo, expliquelo brevemente (qué y cuándo):')}} </label>
                            <textarea type="text" id="ex" name="ex" class="form-control input-full"></textarea>
                            <span class="help-block"></span>
                        </div>
                        <div class = "row mt-3">
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group  controls">
                                <label>{{translate('I prefer mosly:')}}</label>
                                <select name="mosly" id="mosly" class="form-control input-full">
                                    <option value="" disabled selected>Selec Option</option>
                                    <option value="Ligth">Ligth</option>
                                    <option value="Moderate">Moderate</option>
                                    <option value="Deep pressure">Deep pressure</option>
                                </select>
                                <span class="help-block"></span>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group controls">
                                <label>{{translate('Do you like streches?')}}</label>
                                <select name="stre" id="stre" class="form-control input-full">
                                    <option value="" disabled selected>Selec Option</option>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                                <span class="help-block"></span>
                            </div>
                        </div>
                        </div>
                    </div>

                    <div class = "row mt-3"> 
                        <div class="col-md-12 col-sm-12">
                    <div class="form-group control-group form-inline">
                        <label>{{translate('Where in your body do you feel the most tensión/ soreness/ pain?')}} </label>
                        <input type="text" id="mos" name="mos" class="form-control input-full"/>
                        <span class="help-block"></span>
                    </div>
                    </div>
                    </div>

                    <div class = "row mt-3"> 
                        <div class="col-md-12 col-sm-12">
                    <div class="form-group control-group form-inline">
                        <label>{{translate('Is there anything else you would like us to know?')}} </label>
                        <textarea type="text" id="li" name="li" class="form-control input-full"></textarea>
                        <span class="help-block"></span>
                    </div>
                    </div>
                    </div>
                            
                               
                        <!-- -->

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
                            {{translate('Customer Information')}}  
                        </h4>
                        <button id="btnAdd" class="btn btn-primary btn-sm btn-round ml-auto">
                            <i class="fa fa-plus"></i> {{translate('Add New Customer')}}
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="tableElement" class="table table-bordered w100"></table>
                </div>
                <script>
                    function setSystemUser(userType) {
                        // Establece el valor del select oculto
                        document.getElementById('user_id').value = userType;
                
                        // Lógica adicional si es necesaria (por ejemplo, mostrar el modal o formulario relacionado)
                        alert('System User set to: ' + userType);
                    }
                </script>
            </div>
        </div>
    </div>
</div>


@endsection
@extends('layouts.app')
@section('content')
<div class="page-inner">
    <div class="row">
        <div class="col-md-12">
            <div class="main-card card">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <h4 class="card-title">
                            {{translate('Booking Info')}}
                        </h4>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-6">
                                    <span>{{translate('Date From')}}</span>
                                    <input type="text" id="dateFrom" class="form-control input-full datePicker" value="{{now()->sub('30 days')->format('Y-m-d')}}">
                                </div>
                                <div class="col-md-6">
                                    <span>{{translate('Date To')}}</span>
                                    <input type="text" id="dateTo" class="form-control input-full datePicker" value="{{now()->format('Y-m-d')}}">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <span>{{translate('Employee')}}</span>
                            <select id="employeeId" class="form-control input-full" data-live-search="true"></select>
                        </div>
                        <div class="col-md-3">
                            <span>{{translate('Customer')}}</span>
                            <select id="customerId" class="form-control input-full" data-live-search="true"></select>
                        </div>
                       
                    </div>
                    <div class="row mt-2">
                    <div class="col-md-10">
                            <div class="row">
                                <div class="col-md-6">
                                    <span>{{translate('Service Status')}}</span>
                                    <select id="serviceStatus" class="form-control input-full">
                                        <option value="">All</option>
                                        <option selected value="0">Pending</option>
                                        <option value="1">Processing</option>
                                        <option value="2">Approved</option>
                                        <option value="3">Cancel</option>
                                        <option value="4">Done</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <span>Booking No</span>
                                    <input type="number" id="serviceId" placeholder="{{translate('Booking No')}}" class="form-control input-full" />
                                </div>

                            </div>

                        </div>
                        <div class="col-md-2 pt-20">
                            <button id=btnFilter class="btn btn-sm btn-primary float-right"><i class="fas fa-filter"></i> {{translate('Filter')}}</button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table id="tableElement" class="table table-bordered w100"></table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!--Modal-->
    <div class="modal fade" id="frmModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <form class="form-horizontal" id="inputForm" novalidate="novalidate">
                    <div class="modal-body">
                        <h5 class="modal-title">
                            <span class="fw-mediumbold">
                            {{translate('Booking No#')}} <span id="span-booking-no"></span>
                            </span>
                        </h5>
                        <input type="hidden" id="id" name="id" />
                        <div class="form-group control-group form-inline">
                            <div class="row">
                                <div class="col-md-12">
                                    <span>{{translate('Service Status')}}</span>
                                    <select id="status" name="status" class="form-control input-full">
                                        <option selected value="0">Pending</option>
                                        <option value="1">Processing</option>
                                        <option value="2">Approved</option>
                                        <option value="3">Cancel</option>
                                        <option value="4">Done</option>
                                    </select>
                                </div>
                                <div class="col-md-12 control-group">
                                    <div class="form-group control-group form-inline">
                                        <label class="switch">
                                            <input id=email_notify name="email_notify" type="checkbox" value="1" class="rm-slider">
                                            <span class="slider round"></span>
                                        </label>
                                        <label class="pt-1 ml-1"> {{translate('Send notification by email')}}</label>
                                        <span class="help-block"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer pb-0 pr-2">
                            <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">{{translate('Close')}}</button>
                            <button type="submit" class="btn btn-success btn-sm">{{translate('Save Change')}}</button>

                        </div>
                </form>

            </div>
        </div>
    </div>

</div>
@push("adminScripts")
<script src="{{ dsAsset('js/custom/booking/booking-info.js') }}"></script>
@endpush



@endsection
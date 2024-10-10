@extends('layouts.app')
@section('content')
@push("adminCss")
<link href="{{ dsAsset('js/lib/tui.calendar/tui-calendar/dist/tui-calendar.css') }}" rel="stylesheet" />
<link href="{{ dsAsset('js/lib/tui.calendar/tui-time-picker/dist/tui-time-picker.min.css') }}" rel="stylesheet" />
<link href="{{ dsAsset('js/lib/tui.calendar/tui-date-picker/dist/tui-date-picker.min.css') }}" rel="stylesheet" />
<link href="{{ dsAsset('css/custom/employee/salaries.css')}}" rel="stylesheet" />
@endpush
@push("adminScripts")
<script src="{{ dsAsset('js/lib/tui.calendar/tui-code-snippet/dist/tui-code-snippet.min.js') }}"></script>
<script src="{{ dsAsset('js/lib/tui.calendar/tui-time-picker/dist/tui-time-picker.min.js') }}"></script>
<script src="{{ dsAsset('js/lib/tui.calendar/tui-date-picker/dist/tui-date-picker.min.js') }}"></script>
<script src="{{ dsAsset('js/lib/tui.calendar/tui-calendar/dist/tui-calendar.min.js') }}"></script>
<script src="{{ dsAsset('js/lib/tui.calendar/tui-calendar-init.js') }}"></script>
<script src="{{ dsAsset('js/lib/country-list.js') }}"></script>
<script src="{{ dsAsset('js/custom/employee/salaries.js') }}"></script>
@endpush

<div class="page-inner">
    
    <!--datatable-->
    <div class="row">
        <div class="col-md-12">
            <div class="main-card card">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <h4 class="card-title">
                            {{translate('Salary Info')}}
                        </h4>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="">{{translate('Year')}}</label>
                                        <select class="form-control" id="year">
                                            @for($year = date('Y'); $year >= 2010; $year--)
                                            <option>{{$year}}</option>
                                            @endfor                                
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="">{{translate('Month')}}</label>
                                        <select class="form-control" id="month">
                                            @php
                                            $current = date('m');
                                            @endphp
                                            @for($month = 1; $month <= 12; $month++)
                                            @php
                                                $month_date = new \DateTime('2022-'.$month.'-1');
                                            @endphp
                                            <option value="{{$month_date->format('m')}}" @if($current == $month_date->format('m')) selected @endif>{{$month_date->format('F')}}</option>
                                            @endfor                                
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="">{{translate('Employee')}}</label>
                                        <select class="form-control selectpicker" data-live-search="true" id="sch_employee_id">
                                            <option value="">{{translate('All Employees')}}</option>
                                            @foreach($employees as $employee)
                                            <option value="{{$employee->id}}">{{$employee->full_name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group text-right">
                                        <button class="btn btn-primary btn-sm btn-action" id="load-employee">{{translate('Load')}}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group text-right text-md-left">
                                <button class="btn btn-warning btn-sm btn-action" id="download">{{translate('Download')}}</button>
                            </div>
                            <div class="form-group text-right text-md-left">
                                <button class="btn btn-success btn-sm btn-action" id="preview">{{translate('Preview Salary Sheet')}}</button>
                            </div>
                        </div>
                    </div>
                    <table id="tableElement" class="table table-bordered w100"></table>
                    <div class="row">
                        <div class="col-12 text-right mt-3">
                            <button class="btn btn-danger btn-sm btn-action" id="delete">{{translate('Delete')}}</button>
                            <button class="btn btn-success btn-sm btn-action" id="save">{{translate('Save Changes')}}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>




@endsection
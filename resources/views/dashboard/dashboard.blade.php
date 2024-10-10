@extends('layouts.app')
@section('content')
@push("adminCss")
<link href="{{ dsAsset('css/custom/dashboard/dashboard.css')}}" rel="stylesheet" />
@endpush


<div class="panel-header bg-primary-gradient">
    <div class="page-inner py-5">
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row">
            <div>
                <h2 class="text-white pb-2 fw-bold">{{translate('Appointment Booking Dashboard')}}</h2>
            </div>
            <div class="ml-md-auto py-2 py-md-0">
                <a href="booking-calendar" class="btn btn-secondary btn-round">{{translate('Add New Booking')}}</a>
            </div>
        </div>
    </div>
</div>

<div class="page-inner mt--5">
    <div class="row mt--2 div-top-card">

        <div class="col-md-3">
            <div class="card full-height">
                <div class="card-body">
                    <div class="fs-11rem">{{translate('Total Done')}}</div>
                    <div class="d-flex flex-wrap justify-content-around pb-2 pt-2">
                        <div class="px-2 pb-2 pb-md-0 text-center">
                            <h1 class="fw-bold mb-0 mt-2" id="divDoneBookingText">0</h1>
                        </div>
                        <div class="px-2 pb-2 pb-md-0 text-center">
                            <div id="divDoneBooking"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card full-height">
                <div class="card-body">
                    <div class="fs-11rem">{{translate('Total Cancel')}}</div>
                    <div class="d-flex flex-wrap justify-content-around pb-2 pt-2">
                        <div class="px-2 pb-2 pb-md-0 text-center">
                            <h1 class="fw-bold mb-0 mt-2" id="divCancelBookingText">0</h1>
                        </div>
                        <div class="px-2 pb-2 pb-md-0 text-center">
                            <div id="divCancelBooking"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card full-height">
                <div class="card-body">
                    <div class="fs-11rem">{{translate('Total Approved')}}</div>
                    <div class="d-flex flex-wrap justify-content-around pb-2 pt-2">
                        <div class="px-2 pb-2 pb-md-0 text-center">
                            <h1 class="fw-bold mb-0 mt-2" id="divApprovedBookingText">0</h1>
                        </div>
                        <div class="px-2 pb-2 pb-md-0 text-center">
                            <div id="divApprovedBooking"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card full-height">
                <div class="card-body">
                    <div class="fs-11rem">{{translate('Processing & Pending')}}</div>
                    <div class="d-flex flex-wrap justify-content-around pb-2 pt-2">
                        <div class="px-2 pb-2 pb-md-0 text-center">
                            <h1 class="fw-bold mb-0 mt-2" id="divProcessingAndPendingBookingText">0</h1>
                        </div>
                        <div class="px-2 pb-2 pb-md-0 text-center">
                            <div id="divProcessingAndPendingBooking"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row mt--2 div-today-service-card">
        <div class="col-md-7">
            <div class="card full-height">
                <div class="card-body">
                    <div class="card-title">{{translate("Today's Service statistics")}}</div>
                    <div class="card-category">{{translate('Show all service statistics based on user branch permission.')}}</div>
                    <div class="d-flex flex-wrap justify-content-around pb-2 pt-4">
                        <div class="px-2 pb-2 pb-md-0 text-center">
                            <div id="divTotalBookingToday"></div>
                            <h6 class="fw-bold mt-3 mb-0">{{translate('Total')}}</h6>
                        </div>
                        <div class="px-2 pb-2 pb-md-0 text-center">
                            <div id="divDoneBookingToday"></div>
                            <h6 class="fw-bold mt-3 mb-0">{{translate('Done')}}</h6>
                        </div>
                        <div class="px-2 pb-2 pb-md-0 text-center">
                            <div id="divCancelBookingToday"></div>
                            <h6 class="fw-bold mt-3 mb-0">{{translate('Cancel')}}</h6>
                        </div>
                        <div class="px-2 pb-2 pb-md-0 text-center">
                            <div id="divApprovedBookingToday"></div>
                            <h6 class="fw-bold mt-3 mb-0">{{translate('Approved')}}</h6>
                        </div>
                        <div class="px-2 pb-2 pb-md-0 text-center">
                            <div id="divProcessingBookingToday"></div>
                            <h6 class="fw-bold mt-3 mb-0">{{translate('Processing')}}</h6>
                        </div>
                        <div class="px-2 pb-2 pb-md-0 text-center">
                            <div id="divPendingBookingToday"></div>
                            <h6 class="fw-bold mt-3 mb-0">{{translate('Pending')}} </h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card full-height">
                <div class="card-body">
                    <div class="card-title">{{translate("Today's Income & Other Statistics")}}</div>
                    <div class="row py-3">
                        <div class="col-md-6 d-flex flex-column justify-content-around">
                            <div>
                                <h6 class="fw-bold text-uppercase text-success op-8">{{translate('Total Income')}}</h6>
                                <h3 id="totalIncome" class="fw-bold">0</h3>
                            </div>
                            <div>
                                <h6 class="fw-bold text-uppercase text-danger op-8">{{translate('Total Due')}}</h6>
                                <h3 id="totalDue" class="fw-bold">0</h3>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex flex-column justify-content-around">
                            <div>
                                <h6 class="fw-bold text-uppercase text-success op-8">{{translate('Total Cash Payment')}}</h6>
                                <h3 id="totalCash" class="fw-bold">0</h3>
                            </div>
                            <div>
                                <h6 class="fw-bold text-uppercase text-primary op-8">{{translate('Total Online Payment')}}</h6>
                                <h3 id="totalOnlinePayment" class="fw-bold">0</h3>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card full-height">
                <div class="card-header">
                    <div class="card-head-row">
                        <div class="card-title">{{translate('Booking Info')}}</div>

                        <div class="card-tools">

                            <ul class="nav nav-pills nav-secondary nav-pills-no-bd nav-sm" id="pills-tab" role="tablist">
                                <li class="nav-item">
                                    <select id="booking-info-service-status" class="form-control input-sm mt-1">
                                        <option value="">All Except Done</option>
                                        <option value="4">Done</option>
                                        <option value="3">Cancel</option>
                                        <option value="2">Approved</option>
                                        <option value="1">Processing</option>
                                        <option value="0">Pending</option>
                                    </select>
                                </li>
                                <li class="nav-item">
                                    <a class="booking-info-duration nav-link active" id="booking-info-duration-pill-today" data-toggle="pill" href="#pills-today" role="tab" aria-selected="true">{{translate('Today')}}</a>
                                    <input type="radio" id="booking-info-duration-radio-today" checked name="booking-info-duration-radio" value="1" />
                                </li>
                                <li class="nav-item">
                                    <a class="booking-info-duration nav-link" id="booking-info-duration-pill-month" data-toggle="pill" href="#pills-month" role="tab" aria-selected="false">{{translate('Month')}}</a>
                                    <input type="radio" id="booking-info-duration-radio-monthly" name="booking-info-duration-radio" value="2" />
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body scrollbar-outer" id="div-body-booking-info">

                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">{{translate('Top Booking Service')}}</div>
                </div>
                <div class="card-body pb-0" id="div-body-top-booking-service">


                </div>
            </div>
        </div>

    </div>

</div>

@push("adminScripts")
<!-- Chart JS -->
<script src="{{ dsAsset('js/lib/assets/js/plugin/chart.js/chart.min.js') }}"></script>

<!-- jQuery Sparkline -->
<script src="{{ dsAsset('js/lib/assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js') }}"></script>

<!-- Chart Circle -->
<script src="{{ dsAsset('js/lib/assets/js/plugin/chart-circle/circles.min.js') }}"></script>
<!-- jQuery Vector Maps -->
<script src="{{ dsAsset('js/lib/assets/js/plugin/jqvmap/jquery.vmap.min.js') }}"></script>
<script src="{{ dsAsset('js/lib/assets/js/plugin/jqvmap/maps/jquery.vmap.world.js') }}"></script>

<!-- dashboard JS -->
<script src="{{ dsAsset('js/custom/dashboard/main-dashboard.js')}}"></script>
@endpush


@endsection
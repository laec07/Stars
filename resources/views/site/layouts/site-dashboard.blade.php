@extends('site.layouts.site')
@section('content')
@push("css")
<!-- datatable css -->
<link href="{{ dsAsset('js/lib/DataTables/datatables.min.css') }}" rel="stylesheet" />

<link href="{{ dsAsset('site/css/custom/client/site-dashboard.css') }}" rel="stylesheet" />
@endpush




<div class="container mt-5">
	<div class="section-top-border">
		<div class="row mt-5">
			<div class="col-md-3">

				<div class="sidebar-menu">
					<div class="leftside-menu">
						<div class="card card-box-shadow py-4 mb-3">
							<span class="lm-shape"></span> <span class="lm-shape2"></span>
							<div class="card-header bg-transparent">
								<div class="div-profile-image justify-content-center d-flex">
									@if($userInfo['photo']==null || $userInfo['photo']=='')
									<img class="profile-image" src="{{ dsAsset('js/lib/assets/img/avater-man.png') }}" alt="" class="avatar-img rounded" />
									@else
									<img class="profile-image" src="{{ dsAsset($userInfo['photo']) }}" alt="" class="avatar-img rounded" />
									@endif
								</div>
								<h4 class="mb-0 mt-1 text-center fw400">{{$userInfo['name']}}</h4>
								<div class="text-center fs-13 user-balance">
									<span>Balance: {{auth()->user()->balance()}}</span>
								</div>
							</div>
							<ul class="nav flex-column pt-3">
								<li class="nav-item pl-3"><a href="{{route('site.client.profile')}}" class="nav-link"><i class="fa fa-user client-menu-icon"></i> {{translate('Profile')}}</a></li>
								<li class="nav-item pl-3"><a href="{{route('client.dashboard')}}" aria-current="page" class="nav-link"><i class="fa fa-home client-menu-icon"></i> {{translate('Dashboard')}}</a></li>
								<li class="nav-item pl-3"><a href="{{route('site.client.pending.booking')}}" class="nav-link"><i class="fas fa-clock client-menu-icon"></i> {{translate('Pending Booking')}}</a></li>
								<li class="nav-item pl-3"><a href="{{route('site.client.done.booking')}}" class="nav-link"><i class="fa fa-check-circle client-menu-icon"></i> {{translate('Done Booking')}}</a></li>
								<li class="nav-item pl-3"><a href="{{route('site.client.order.index')}}" class="nav-link"><i class="fas fa-shopping-cart client-menu-icon"></i> {{translate('Orders')}}</a></li>
								<li class="nav-item pl-3"><a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="nav-link"><i class="fas fa-sign-out-alt client-menu-icon"></i> {{translate('Sign Out')}}</a></li>
								<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
									@csrf
								</form>
							</ul>
						</div>
					</div>
				</div>
			</div>

			<div class="col-md-9">
				@yield('content-site-dashboard')
			</div>
		</div>

	</div>


</div>


@push("scripts")
<script src="{{ dsAsset('site/js/custom/client/site-dashboard.js') }}"></script>

<!-- Datatables -->
<script src="{{ dsAsset('js/lib/DataTables/datatables.min.js') }}"></script>
@endpush

@endsection
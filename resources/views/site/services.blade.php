	@extends('site.layouts.site')
	@section('content')
	<!--start banner section -->
	<section class="banner-area position-relative" style="background:url({{$appearance->background_image}}) no-repeat;">
		<div class="overlay overlay-bg"></div>
		<div class="container">
			<div class="row">
				<div class="col-md-12">
					<div class="position-relative text-center">
						<h1 class="text-capitalize mb-3 text-white">{{translate('Our Services')}}</h1>
						<a class="text-white" href="{{route('site.home')}}">{{translate('Home')}} </a>
						<i class="icofont-long-arrow-right text-white"></i>
						<a class="text-white" href="{{route('site.menu.services')}}"> {{translate('Service')}}</a>
					</div>
				</div>
			</div>
		</div>
	</section>
	<!-- end banner section -->

	<!-- Start Service Area -->
	<section class="top-area section-gap">
		<div class="container">
			<div class="row d-flex justify-content-center">
				<div class="col-lg-9">
					<div class="text-center pb-3">
						<h2 class="mb-10">{{translate('Available Our Services')}}</h2>
						<p>{{translate('All available services of our all branches you can choose any service based on your need.')}}</p>
					</div>
				</div>
			</div>
			<div class="row">
				@foreach ($services as $value)
				<div class="col-lg-4">
					<div class="single-service single-service-services">
						<div class="thum">
							<img src="{{$value->image}}" alt="">
						</div>
						<div class="details pt-3">
						<div class="star mb-3">
								
								@for($i=1;$i<=5;$i++) 
									@if ($value->avgRating>=$i)
										<span class="fa fa-star checked"></span>
									@else 
										<span class="fa fa-star"></span>
									@endif
								@endfor
									({{$value->countRating}})
							</div>
							<h4>{{$value->title}}</h4>
							<p>{{$value->remarks}}</p>
							<ul class="single-service-info">
								<li class="d-flex justify-content-between align-items-center">
									<span>{{translate('Total Service Time')}}</span>
									<span>{{$value->time_slot_in_time}} minute</span>
								</li>
								<li class="d-flex justify-content-between align-items-center">
									<span>{{translate('Service Limit')}} {{$value->appoinntment_limit_type}}</span>
									<span>{{$value->appoinntment_limit}}</span>
								</li>
								<li class="d-flex justify-content-between align-items-center">
									<span>{{translate('Price per service')}} </span>
									<span>{{$value->price}}</span>
								</li>
								<li class="d-flex justify-content-between align-items-center">
									<span>{{$value->visibility}}</span>
									<a href="{{route('site.appoinment.booking')}}" class="btn btn-booking-white">{{translate('Book Now')}}</a>
								</li>
							</ul>

							
						</div>
					</div>
				</div>
				@endforeach
			</div>
		</div>
	</section>
	<!-- End service Area -->
	@endsection
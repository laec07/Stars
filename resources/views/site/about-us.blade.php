@extends('site.layouts.site')
@section('content')
<!--start banner section -->
<section class="banner-area position-relative" style="background:url({{$appearance->background_image}}) no-repeat;">
	<div class="overlay overlay-bg"></div>
	<div class="container">
		<div class="row">
			<div class="col-md-12">
				<div class="position-relative text-center">
					<h1 class="text-capitalize mb-3 text-white">{{translate('About Us')}}</h1>
					<a class="text-white" href="{{route('site.home')}}">{{translate('Home')}} </a>
					<i class="icofont-long-arrow-right text-white"></i>
					<a class="text-white" href="{{route('site.about.us')}}"> {{translate('About Us')}}</a>
				</div>
			</div>
		</div>
	</div>
</section>
<!-- end banner section -->

<!-- Start about-info Area -->
<section class="section-gap top-about-us">
	<div class="container">
		@foreach ($aboutUs as $value)
		<div class="row single-about-us">

			<div class="col-lg-5 col-md-5">
				<img class="img-fluid" src="{{$value->image_url}}" alt="">
			</div>
			<div class="col-lg-7 col-md-7 about-us-details">
				<p class="about-us-head">{{translate('24/7 ABOUT US')}}</p>
				<h2>{{$value->title}}</h2>
				<p>
					{{$value->details}}
				</p>
				<div class="row p-2 about-service-quality">
					<div class="col-md-6"><i class="fas fa-check-circle"></i> {{translate('24/7 Hours online booking')}}</div>
					<div class="col-md-6"><i class="fas fa-check-circle"></i> {{translate('Expertise staffs')}}</div>
				</div>
				<div class="row p-2 about-service-quality">
					<div class="col-md-6"><i class="fas fa-check-circle"></i> {{translate('On time service delivery')}}</div>
					<div class="col-md-6"><i class="fas fa-check-circle"></i> {{translate('Top quality services')}}</div>
				</div>
				<div class="col about-us-btns mt-5">
					<a href="{{route('site.menu.services')}}" class="btn btn-booking-white">{{translate('See our services')}}</a>
					<a href="{{(route('site.contact'))}}" class="btn btn-booking">{{translate('Contact with us')}}</a>
				</div>
			</div>
		</div>
		@endforeach

	</div>
</section>
<!-- End about-info Area -->


<!-- Start Team Area -->
<section class="section-gap expertise-staff-section">
	<div class="container">
		<div class="row d-flex justify-content-center">
			<div class="col-lg-9 header-expertise">
				<div class="text-center pb-3">
					<p>{{translate('OUR SPECIALIST')}}</p>
					<h2 class="mb-10">{{translate('Meet Our Specialist')}}</h2>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-md-12">
				<div class="expertise-team owl-carousel owl-theme">
					@foreach ($expertiseEmployee as $value)
					<div class="single-item">
						<div class="thum">
							<img class="img-fluid" src="{{$value->image_url}}" alt="Employee Image">
						</div>
						<div class="details">
							<h4 class="title">{{$value->full_name}}</h4>
							<p>
								{{$value->specialist}}
							</p>
						</div>
					</div>
					@endforeach
				</div>
			</div>
		</div>
	</div>
</section>
<!-- End Team Area -->


<!-- Start Client Testimonial Area -->
<section class="section-gap section-client-say">
	<div class="container">
		<div class="row d-flex justify-content-center">
			<div class="col-lg-9 header-client-say">
				<div class="text-center pb-3">
					<p>{{translate('OUR CLIENTS')}}</p>
					<h2 class="mb-10">{{translate('Valuable Clients Comments')}}</h2>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-8 offset-md-2">
				<div class="client-say-about-us owl-carousel owl-theme">
					@foreach ($clientTestimonial as $value)
					<div class="client-single-say-about-us">
						<div class="w100 d-flex justify-content-center">
							<div class="thum">
								<img src="{{$value->image}}" alt="">
							</div>
						</div>
						<div class="desctiotion">
							<p class="text-center">
								{{$value->description}}
							</p>
							<h4 class="d-flex justify-content-center">{{$value->name}}</h4>

						</div>
					</div>
					@endforeach
				</div>
			</div>
		</div>
	</div>
</section>
<!-- End Client testimonial-->

@endsection
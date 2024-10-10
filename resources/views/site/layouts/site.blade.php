<!DOCTYPE html>
<html lang="zxx" class="no-js" dir="{{$rtl}}">

<head>
	<meta name="_token" content="{{ csrf_token() }}" url="{{ url('/') }}" />
	<!-- Mobile Specific Meta -->
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<!-- Favicon-->
	<link rel="shortcut icon" href="{{url($appearance->icon)}}">
	<!-- Meta Description -->
	<meta name="description" content="{{$appearance->meta_description}}">
	<!-- Meta Keyword -->
	<meta name="keywords" content="{{$appearance->meta_keywords}}">
	<!-- meta character set -->
	<meta charset="UTF-8">
	<!-- Site Title -->
	<title>{{$appearance->app_name}}</title>
	<link rel="stylesheet" href="{{dsAsset('site/assets/css/bootstrap.min.css')}}">
	<link rel="stylesheet" href="{{dsAsset('site/assets/js/lib/icofont/icofont.min.css')}}">
	<link rel="stylesheet" href="{{dsAsset('site/assets/js/lib/fontawesome/css/all.min.css')}}">
	<link rel="stylesheet" href="{{dsAsset('site/assets/js/lib/owl-carousel/assets/owl.theme.default.min.css')}}">
	<link rel="stylesheet" href="{{dsAsset('site/assets/js/lib/owl-carousel/assets/owl.carousel.min.css')}}">
	<link rel="stylesheet" href="{{dsAsset('site/assets/js/lib/magnific-popup/magnific-popup.css')}}">
	<link rel="stylesheet" href="{{dsAsset('site/assets/css/app.css')}}">
	<link href="{{dsAsset('js/lib/xd-dpicker/jquery.datetimepicker.css')}}" rel="stylesheet" />
	<link href="{{dsAsset('js/lib/tel-input/css/intlTelInput.css')}}" rel="stylesheet" />


	<style>
		:root {
		--theamColor: {{$appearance["theam_color"]}};
		--theamHoverColor: {{$appearance["theam_hover_color"]}};
		--theamActiveColor: {{$appearance["theam_active_color"]}};
		--theamMenuColor: {{$appearance["menu_color"]}};
		--theamMenuColor2: {{$appearance["menu_color2"]}};
		--theamColorRgba:{{hex2Rgba($appearance["theam_color"],0.1)}};
	}
	</style>

	@stack('css')
</head>

<body id="process_notifi">
	<header class="header">
		<div class="header-top">
			<div class="container">
				<div class="row align-items-center">
					<div class="col-md-6 header-top-left">
						<a href="{{$appearance->faccebook_link}}"><i class="fab fa-facebook-f fs-13"></i></a>
						<a href="{{$appearance->twitter_link}}"><i class="fab fa-twitter fs-13"></i></a>
						<a href="{{$appearance->youtube_link}}"><i class="fab fa-youtube fs-13"></i></a>
						<a href="{{$appearance->instagram_link}}"><i class="fab fa-instagram fs-13"></i></a>
					</div>
					<div class="col-md-6">
						<div class="text-lg-end mt-2 mt-lg-0 header-top-right {{$rtl=='rtl'?'float-start':'float-end	'}}">
							<form id="language-change-form" class="float-start" action="{{ route('change.language') }}" method="POST">
								@csrf
								<select id="cmbLang" class="me-3" name="lang_id">
									@foreach ($language as $lang)
									<option {{(Session::get('lang')!=null) && (Session::get('lang')['id'])==$lang->id?"selected":""}} value={{$lang->id}}>{{$lang->name}}</option>
									@endforeach
								</select>
							</form>



							<a class="me-3 color-white fs-12" href="{{route('site.appoinment.booking')}}"><span> <i class="far fa-clock"></i> {{translate('Book Now')}}</span></a>

							<a class="me-3 color-white fs-12 cart" href="{{route('site.cart')}}"><i class="fas fa-shopping-cart"></i><span class="cart-count" id="cart-count">{{session()->get('user_cart',collect([]))->count()}}</span></a>
							@if (auth()->check() && auth()->user()->user_type==2)
							<a class="me-3 color-white" href="{{route('client.dashboard')}}">{{translate('My Panel')}}</a>
							@else
							<a class="me-3 color-white fs-12" href="{{route('register')}}"><i class="fas fa-user-plus"></i> {{translate('Sign Up')}}</a>
							<a class="me-3 color-white fs-12" href="{{route('login')}}"><i class="fas fa-sign-in-alt"></i> {{translate('Sign In')}}</a>
							@endIf
						</div>
					</div>
				</div>
			</div>
		</div>
		<nav class="navbar navbar-expand-lg navbar-light navigation" id="navbar">
			<div class="container">
				<a class="navbar-brand" href="{{route('site.home')}}">
					<img src="{{dsAsset($appearance->logo)}}" />
				</a>
				<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-main" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse {{$rtl=='rtl'?'navbar-collapse-rtl':''}} navbar-collapse" id="navbar-main">
					<ul class="navbar-nav ms-auto">
						@foreach ($menuList->where('site_menu_id', 0) as $mTop)
						<li class="nav-item dropdown">
							<a class="nav-link dropdown-toggle" href="{{route($mTop->route)}}" id="navbarDropdownMenuLink" role="button" data-bs-toggle="@if($menuList->where('site_menu_id', $mTop->id)->count()>0) dropdown @endif" aria-expanded="false">{{translate($mTop->name)}}
								@if($menuList->where('site_menu_id', $mTop->id)->count()>0)
								<i class="icofont-thin-down"></i>
								@endif
							</a>
							@if($menuList->where('site_menu_id', $mTop->id)->count()>0)

							<ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
								@foreach ($menuList->where('site_menu_id', $mTop->id) as $c1)
								<li><a class="dropdown-item" href="{{route($c1->route)}}">{{translate($c1->name)}}</a>
								</li>
								@endforeach
							</ul>
							@endif
						</li>
						@endforeach
					</ul>
				</div>
			</div>
		</nav>
	</header>
	<!--end header -->

	@yield('content');

	<!-- Start footer-->
	<footer class="footer section-gap">
		<div class="container">
			<div class="row mb-5">
				<div class="col-lg-4 col-md-6 col-sm-6">
					<div class="footer-widget">
						<h3>{{translate('About Service')}}</h3>
						<p>
							{{$appearance->about_service}}
						</p>
					</div>
				</div>
				<div class="col-lg-3 col-md-6 col-sm-6">
					<div class="footer-widget">
						<h3>{{translate('Website Navigation Links')}}</h3>
						<div class="row">
							<div class="col">
								<ul>
									@foreach ($menuList->where('site_menu_id', 0)->skip(0)->take(4) as $mTop)
									<li><a href="{{route($mTop->route)}}">{{$mTop->name}}</a></li>
									@endforeach
								</ul>
							</div>
							<div class="col">
								<ul>
									@foreach ($menuList->where('site_menu_id', 0)->skip(4) as $mTop)
									<li><a href="{{route($mTop->route)}}">{{$mTop->name}}</a></li>
									@endforeach
									<li><a href="{{route('site.terms.and.condition')}}">{{translate('Terms & Conditions')}}</a></li>
								</ul>
							</div>
						</div>
					</div>
				</div>
				<div class="col-lg-3 col-md-6 col-sm-6">
					<div class="footer-widget">
						<h3>{{translate('Contact Information')}}</h3>
						<ul>
							<li><a href="#">{{translate('Phone')}} : {{$appearance->contact_phone}}</a></li>
							<li><a href="#">{{translate('Email to')}} : {{$appearance->contact_email}}</a></li>
							<li><a href="#">{{translate('Website')}} : {{$appearance->contact_web}}</a></li>
							<li><a href="#">{{translate('Address')}} : {{$appearance->address}}</a></li>
						</ul>
					</div>
				</div>
				<div class="col-lg-2 col-md-6 col-sm-6">
					<div class="footer-widget">
						<h3 class="mb-20">{{translate('Payment Method')}}</h3>
						<ul class="d-flex flex-wrap">
							<li class="p-1"><img src="img/paypal.png" width="50" alt=""></li>
							<li class="p-1"><img src="img/stripe.png" width="50" alt=""></li>
						</ul>
					</div>
				</div>
			</div>

			<div class="row footer-button-section d-flex justify-content-between align-items-center">
				<div class="col-lg-7 col-sm-12 fs-13">
					Copyright &copy; {{now()->year}} All rights reserved | {{$appearance->app_name}}
				</div>
				<p class="col-lg-5 col-sm-12 footer-social-media">
					<a href="{{$appearance->faccebook_link}}"><i class="fab fa-facebook-f fs-13"></i></a>
					<a href="{{$appearance->twitter_link}}"><i class="fab fa-twitter fs-13"></i></a>
					<a href="{{$appearance->youtube_link}}"><i class="fab fa-youtube fs-13"></i></a>
					<a href="{{$appearance->instagram_link}}"><i class="fab fa-instagram fs-13"></i></a>

				</p>

			</div>
		</div>
	</footer>
	<link href="https://fonts.googleapis.com/css?family=Exo:500,600,700|Roboto&display=swap" rel="stylesheet" />
	<script src="{{dsAsset('site/assets/js/jquery.min.js') }}"></script>

	<!-- datetime pciker js -->
	<script src="{{ dsAsset('js/lib/tel-input/js/intlTelInput.js') }}"></script>
	<script src="{{ dsAsset('js/lib/moment.js') }}"></script>
	<script src="{{ dsAsset('js/lib/jquery.steps/jquery.steps.min.js') }}"></script>
	<link href="{{ dsAsset('js/lib/jquery.steps/jquery.steps.css') }}" rel="stylesheet" />
	<link rel="stylesheet" href="{{dsAsset('site/css/website.css')}}">
	<script src="{{dsAsset('site/js/custom/website.js')}}"></script>

	<!-- End footer -->
	<script src="{{dsAsset('site/assets/js/bootstrap.min.js') }}"></script>
	<script src="{{dsAsset('site/assets/js/popper.min.js') }}"></script>
	<script src="{{dsAsset('site/assets/js/easing.js') }}"></script>
	<script src="{{dsAsset('site/assets/js/lib/owl-carousel/owl.carousel.min.js') }}"></script>
	<script src="{{dsAsset('site/assets/js/lib/magnific-popup/jquery.magnific-popup.min.js') }}"></script>
	<script src="{{dsAsset('site/assets/js/main.js') }}"></script>
	<!--notify JS-->
	<script src="{{ dsAsset('js/lib/assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js') }}"></script>
	<!--JQ bootstrap validation-->
	<script src="{{ dsAsset('js/lib/assets/js/plugin/jquery-bootstrap-validation/jqBootstrapValidation.js') }}"></script>
	<script src="{{ dsAsset('js/lib/xd-dpicker/build/jquery.datetimepicker.full.min.js') }}"></script>
	<script src="{{ dsAsset('js/site.js') }}"></script>
	<script src="{{ dsAsset('js/lib/js-manager.js') }}"></script>
	<script src="{{ dsAsset('js/lib/js-message.js') }}"></script>
	@stack('scripts')

</body>

</html>
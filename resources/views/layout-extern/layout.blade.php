<!DOCTYPE html>
<html lang="nl">

<head>
	<meta name="description" content="{{instelling('stek', 'beschrijving')}}">
	<meta name="google-site-verification" content="zLTm1NVzZPHx7jiGHBpe4HeH1goQAlJej2Rdc0_qKzE"/>
	<meta property="og:url" content="{{CSR_ROOT}}{{REQUEST_URI}}"/>
	<meta property="og:title" content="C.S.R. Delft | @yield('titel')"/>
	<meta property="og:locale" content="nl_nl"/>
	<meta property="og:image" content="{{CSR_ROOT}}/images/beeldmerk.png"/>
	<meta property="og:description" content="{{instelling('stek', 'beschrijving')}}"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	{!! csrfMetaTag() !!}
	<title>C.S.R. Delft - @yield('titel')</title>
	<link rel="shortcut icon" href="{{CSR_ROOT}}/images/favicon.ico"/>
	<link rel="alternate" title="C.S.R. Delft RSS" type="application/rss+xml"
				href="{{CSR_ROOT}}/forum/rss.xml"/>
	@yield('styles')
	@script('extern.js')
</head>

<body>
<script>document.body.classList.add('is-loading');</script>
<!-- Page Wrapper -->
<div id="page-wrapper">

	<!-- Header -->
	<header id="header" class="alt">
		<nav id="menu">
			{{--			<a class="nav-link" href="/owee">Owee</a>--}}
			@foreach(get_menu('extern', true)->children as $menuItem)
				@if(count($menuItem->children) > 0)
					<span class="dropdown-menu">
						<a href="{{$menuItem->link}}" class="nav-link dropdown-link">{{$menuItem->tekst}} <span class="expand-dropdown"><i class="fa fa-plus"></i></span></a>
						<span class="dropdown">
							@foreach($menuItem->children as $childMenuItem)
								<a href="{{$childMenuItem->link}}">{{$childMenuItem->tekst}}</a>
							@endforeach
						</span>
					</span>
				@else
					<a class="nav-link" href="{{$menuItem->link}}">{{$menuItem->tekst}}</a>
				@endif
			@endforeach
		</nav>
		<nav class="nav-login">
			@section('loginbutton')
				<a class="login-knop" href="#login"><i class="fa fa-2x fa-fw fa-user"></i></a>
				<a class="nav-link inloggen" href="#login">Inloggen</a>
			@show
			<a href="#menu" class="menu-knop"><i class="fa fa-2x fa-fw fa-bars"></i></a>
		</nav>
	</header>

@section('loginpopup')
	<!-- Loginform -->
		<nav id="login">
			<a href="#_" class="overlay"></a>
			<div class="inner">
				<h2>Inloggen</h2>
				@php((new \CsrDelft\view\login\LoginForm())->view())
				<a href="#_" class="close">Close</a>
			</div>
		</nav>
@show

@section('body')
	<!-- Banner -->
		<section id="banner-small">
			<div class="inner">
				<a href="/"><img src="/images/c.s.r.logo.svg" alt="Beeldmerk van de vereniging" height="140">
					<h2>C.S.R. Delft</h2></a>
			</div>
		</section>

		<!-- Wrapper -->
		<section id="wrapper">
			<section class="wrapper detail kleur1">
				<div class="inner">
					<div class="content">
						@yield('content')
					</div>
				</div>
			</section>
			<section id="footer">
				<div class="inner">
					<ul class="copyright">
						<li>&copy; {{date('Y')}} - C.S.R. Delft - <a
								href="/download/Privacyverklaring%20C.S.R.%20Delft%20-%20Extern%20-%2025-05-2018.pdf">Privacy</a></li>
					</ul>
				</div>
			</section>
		</section>
	@show
</div>
</body>
</html>

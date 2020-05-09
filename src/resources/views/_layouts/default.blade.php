<!DOCTYPE html>
<html lang="{{ config('ed.view_locale') }}" prefix="og: http://ogp.me/ns#">
<head>
  <title>@yield('title') - {{ config('ed.title') }}</title>
  <meta charset="UTF-8">
  <meta property="og:title" content="@yield('title') - {{ config('ed.title') }}">
  <meta property="og:description" content="{{ config('ed.description') }}">
  <meta property="og:locale" content="{{ config('ed.view_locale') }}">
  <meta name="description" content="{{ config('ed.description') }}">
  <meta name="keywords" content="sindarin, quenya, noldorin, quendya, elvish, tolkien, nandorin, ilkorin, black speech, westron">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="theme-color" value="#333333">
  <meta name="csrf-token" id="ed-csrf-token" content="{{ csrf_token() }}">
  <link rel="apple-touch-icon-precomposed" href="/img/favicons/apple-touch-icon-precomposed.png">
  <link rel="icon" type="image/png" href="/img/favicons/favicon-194x194.png">
  <link rel="manifest" href="/img/favicons/manifest.json">
  <link href="@assetpath(/index.css)" rel="stylesheet">
  @yield('styles')
  @if (!empty(config('ed.header_view')))
    @include(config('ed.header_view'))
  @endif
  <noscript><style type="text/css">.noscript--hidden{display:none;}</style></noscript>
</head>
<body class="{{ $isAdmin ? 'ed-admin' : ($isAdmin === false ? 'ed-user' : 'ed-anonymous') }}" data-account-id="{{ $user ? $user->id : '0' }}" data-v="{{ config('ed.version') }}">
  <!-- navigation -->
  <nav class="navbar navbar-dark bg-dark navbar-expand-lg">
    <a class="navbar-brand" href="/">{{ config('ed.title') }}</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
      <ul class="nav navbar-nav">
        <li class="nav-item {{ active('home') }}">
          <a class="nav-link" href="/">@lang('home.title')</a>
        </li>
        <li class="nav-item {{ active('about') }}">
          <a class="nav-link" href="{{ route('about') }}">@lang('about.title')</a>
        </li>
        <li class="nav-item {{ active(['sentence.public', 'sentence.public.language', 'sentence.public.sentence']) }}">
          <a class="nav-link" href="{{ route('sentence.public') }}">@lang('sentence.title')</a>
        </li>
        <li class="nav-item {{ active(['flashcard', 'flashcard.cards']) }}">
          <a class="nav-link" href="{{ route('flashcard') }}">@lang('flashcard.title')</a>
        </li>
        <li class="nav-item {{ active(['discuss.index', 'discuss.group', 'discuss.show', 'discuss.member-list']) }}">
          <a class="nav-link" href="nav-item {{ route('discuss.index') }}">@lang('discuss.title')</a>
        </li>
      </ul>
      <ul class="nav navbar-nav navbar-right">
        <li class="nav-item dropdown">
          <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" role="button">
            @if ($user)
              {{ $user->nickname }}
            @else
              @lang('discuss.community.title')
            @endif
            <span class="caret"></span>
          </a>
          <div class="dropdown-menu">
            @if ($user)
            <a class="dropdown-item {{ active('author.my-profile') }}" href="{{ route('author.my-profile') }}">
              <span class="glyphicon glyphicon-user"></span> 
              &nbsp;@lang('discuss.community.profile')
            </a>
            <a class="dropdown-item {{ active('dashboard') }}" href="{{ route('dashboard') }}">
              <span class="glyphicon glyphicon-dashboard"></span> 
              &nbsp;@lang('dashboard.title')
            </a>
            <a class="dropdown-item" href="{{ route('logout') }}">
              <span class="glyphicon glyphicon-log-out"></span> 
              &nbsp;@lang('dashboard.logout')
            </a>
            @else
            <a class="dropdown-item {{ active('login') }}" href="{{ route('login') }}">
              <span class="glyphicon glyphicon-log-in"></span> 
              &nbsp;@lang('dashboard.login')
            </a>
            <a href="dropdown-item {{ route('login') }}">
              <span class="glyphicon glyphicon-user"></span> 
              &nbsp;@lang('dashboard.register')
            </a>
            @endif
            <a class="dropdown-item {{ active('discuss.members') }}" href="{{ route('discuss.members') }}">
              @lang('discuss.member-list.title')
            </a>
          </div>
        </li>
      </ul>
    </div>
  </nav>
  <!-- /navigation -->
  <main class="container">
    <!-- scripting disabled warning -->
    <noscript>
      <div id="noscript" class="alert alert-danger">
        <strong><span class="glyphicon glyphicon-flag" aria-hidden="true"></span> @lang('home.noscript.title')</strong>
        <p>@lang('home.noscript.message', ['website' => config('ed.title')])</p>
        <p><a href="https://support.google.com/bin/answer.py?hl=en&amp;answer=23852" target="_blank">@lang('home.noscript.call-to-action')</a>.</p>
      </div>
    </noscript>

    <!-- search component -->
    <div id="ed-search-component"></div>

    <!-- begin content -->
    @yield('body')
  </main>
  <footer class="footer bg-dark mt-auto py-3">
    <section>
      <h3>{{ config('ed.title') }}</h3>
      <nav>
        <ul>
          <li><a href="{{ route('login') }}">Log in</a></li>
          <li><a href="{{ route('about') }}">About the website</a></li>
          <li><a href="{{ route('about.cookies') }}">Cookie policy</a></li>
          <li><a href="{{ route('about.privacy') }}">Privacy policy</a></li>
        </ul>
      </nav>
    </section>
    <section>
      Black Speech, Nandorin, Noldorin, Quendya, Quenya, Sindarin, Telerin are languages conceived by Tolkien and they do not belong to us; 
      we neither can nor do claim affiliation with <a href="http://www.middleearth.com/" target="_blank">Middle-earth Enterprises</a> nor 
      <a href="http://www.tolkienestate.com/" target="_blank">Tolkien Estate</a>.
    </section>
  </footer>

  <script type="text/javascript" src="@assetpath(vendor.js)"></script>
  <script type="text/javascript" src="@assetpath(index.js)"></script>
  
  @yield('scripts')
  @if (!empty(config('ed.footer_view')))
    @include(config('ed.footer_view'))
  @endif
  </body>
</html>

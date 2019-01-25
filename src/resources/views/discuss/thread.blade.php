@extends('_layouts.default')

@section('title', $thread->subject.' - Discussion')
@section('body')
  <article>
    <nav class="discuss-breadcrumbs">
      {!! Breadcrumbs::render('discuss.show', $group, $thread) !!}
    </nav>
    <header>
      <h1>{{ $thread->subject }}</h1>
    </header>
    <section class="discuss-entity">
    {!! $context->view($thread->entity) !!}
    </section>
    <aside id="discuss-toolbar" data-thread-id="{{ $thread->id }}"></aside>
    <section class="discuss-body" 
             data-inject-module="discuss"
             data-inject-prop-current-page="@json($preloadedPosts['current_page'])"
             data-inject-prop-no-of-pages="@json($preloadedPosts['no_of_pages'])"
             data-inject-prop-pages="@json($preloadedPosts['pages'])"
             data-inject-prop-posts="@json($preloadedPosts['posts'])"
             data-inject-prop-thread="@json($thread)">
      @foreach ($preloadedPosts['posts'] as $post)
        @include('discuss._post', $post)
      @endforeach
      @include('discuss._pagination', $preloadedPosts)
    </section>
  </article>
@endsection
@section('styles')
<link rel="stylesheet" href="@assetpath(style-discuss.css)">
@endsection
@section('scripts')

@endsection

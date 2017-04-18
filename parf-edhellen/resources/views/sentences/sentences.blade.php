@inject('link', 'App\Helpers\LinkHelper')
@extends('_layouts.default')

@section('title', 'Phrases')
@section('body')

  {!! Breadcrumbs::render('sentences.language', $language->ID, $language->Name) !!}

  <header>
      @include('sentences._header')
      <h2>{{ $language->Name }} <span class="tengwar" aria-hidden="true">{{ $language->Tengwar }}</span></h2>
  </header>
  @foreach ($sentences as $sentence)
  <blockquote>
    <h3>{{ $sentence->Name }}</h3>
    @if(!empty($sentence->Description))
    <p>{{ $sentence->Description }}</p>
    @endif
    <footer>{{ $sentence->Source }}</footer>
    
    @include('sentences._readmore', [ 
      'languageId'     => $language->ID,
      'languageName'   => $language->Name,
      'sentenceId'     => $sentence->SentenceID,
      'sentenceName'   => $sentence->Name
    ])
  </blockquote>
  @endforeach
@endsection
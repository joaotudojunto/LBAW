@extends('layouts.app')
@section('page-title', $tag->name.' | ')
@section('content')
@include('partials.navbar')
@push('scripts')
    <script defer src = {{ asset('js/ajax.js') }}></script>
    <script defer src = {{ asset('js/contentload.js') }}></script>
    <script defer src = {{ asset('js/tag.js') }}></script>
    @auth
    <script defer src = {{ asset('js/voting.js') }}></script>
    <script defer src = {{ asset('js/follow_tag.js') }}></script>
    <script defer src = {{ asset('js/report.js') }}></script>
    @endauth

    @guest
    <script defer src = {{ asset('js/login_required.js') }}></script>
    @endguest

    <script defer src = {{ asset('js/footer.js') }}></script>
@endpush
<section class="container g-0 mx-auto my-4 col-lg-7 ">
    <header class="p-3 p-lg-5 mb-3 bg-white rounded" style="height:fit-content">
        <section class="row g-0 align-items-center" id="tag-info">
            <div class="col-md-10 col-8">
                <div class="row g-0">
                    <img src="{{ URL::asset('/storage/tag/'.$tag->id.'.png') }}" class="rounded-circle col-2 d-none d-sm-block px-0" alt="tag image" style = "max-width: 100px">
                    <div class="col-10 px-3 my-auto d-flex flex-column">
                        <h3 class="h2 fw-bold" id="tag_name">{{$tag->name}}</h3>
                        <h5 id="tag_followers" data_id={{$tag->id}}>{{$tag->followers->count()}} Followers</h5>
                    </div>
                </div>
            </div>

            <div class="col-md-2 col-4 d-flex justify-content-end reportable" data-id="{{$tag->id}}">
                @auth
                @if (($tag->isFollowed(Auth::user()->id)) != null)
                    <button type="button" class="following-button btn btn-outline-primary tag-follow-button" data-id="{{$tag->id}}"></button>
                @else
                    <button type="button" class="follow-button btn btn-outline-primary tag-follow-button" data-id="{{$tag->id}}"></button>
                @endif

                @endauth
                @guest
                <button type="button" class="follow-button btn btn-outline-primary tag-follow-button" data-id="{{$tag->id}}"></button>


                </button>
                @endguest
            </div>
        </section>
    </header>

    <section class="pill-navigation mb-1">
        <ul class="nav nav-pills mb-1 justify-content-space-between bg-white rounded" id="pills-tab"
            role="tablist">
            <li class="nav-item col" role="presentation">
                <button class="nav-link active w-100" id="pills-trending-tab" data-bs-toggle="pill"
                    data-bs-target="#pills-trending" type="button" role="tab" aria-controls="pills-trending"
                    aria-selected="false">Trending</button>
            </li>
            <li class="nav-item col" role="presentation">
                <button class="nav-link w-100" id="pills-latest-tab" data-bs-toggle="pill"
                    data-bs-target="#pills-latest" type="button" role="tab" aria-controls="pills-latest"
                    aria-selected="false">Latest</button>
            </li>
        </ul>
    </section>

    <section id="content" class="posts reportable"></section>
    <div id="spinner" class="d-flex justify-content-center mt-5">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

</section>
@guest
@include('partials.login_required')
@endguest
@include('partials.footer')
@endsection

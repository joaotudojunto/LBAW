@extends('layouts.app')
@section('page-title', 'Home | ')
@section('content')
    @include('partials.navbar')
    @push('scripts')
        <script defer src = {{ asset('js/ajax.js') }}></script>
        <script defer src = {{ asset('js/contentload.js') }}></script>
        <script defer src = {{ asset('js/tooltip.js') }}></script>
        <script defer src = {{ asset('js/home.js') }}></script>

        @auth
        <script defer src = {{ asset('js/voting.js') }}></script>
        <script defer src = {{ asset('js/report.js') }}></script>
        @endauth

        @guest
        <script defer src = {{ asset('js/login_required.js') }}></script>
        @endguest

        <script defer src = {{ asset('js/footer.js') }}></script>
    @endpush
    <section class="mainpage-container container my-4 col-lg-8 px-0 mt-md-4">
        <div class="row justify-content-evenly g-0">
            <section class="all-news-cards col-md-8">
                <section class="pill-navigation">
                    <ul class="nav nav-pills mb-1 bg-white rounded" id="pills-tab" role="tablist">
                        @auth
                            <li class="nav-item col" role="presentation">
                                <button class="nav-link active w-100" id="pills-feed-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-feed" type="button" role="tab" aria-controls="pills-feed"
                                        aria-selected="true">Feed
                                        <span class="tt" data-toggle="tooltip" title="Posts from tags and users you are following">
                                            <i class="material-symbols-outlined inline-icon">info</i>
                                        </span>
                                </button>
                            </li>
                        @endauth
                        <li class="nav-item col" role="presentation">
                            <button class="nav-link @guest active @endguest w-100" id="pills-trending-tab"
                                    data-bs-toggle="pill"
                                    data-bs-target="#pills-trending" type="button" role="tab"
                                    aria-controls="pills-trending"
                                    aria-selected="@auth false @endauth @guest true @endguest">Trending
                                    <span class="tt" data-toggle="tooltip" title="Most-voted posts from the last 14 days">
                                        <i class="material-symbols-outlined inline-icon">info</i>
                                    </span>

                            </button>
                        </li>
                        <li class="nav-item col" role="presentation">
                            <button class="nav-link w-100" id="pills-latest-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-latest" type="button" role="tab" aria-controls="pills-latest"
                                    aria-selected="false">Latest
                                    <span class="tt" data-toggle="tooltip" title="Most recent posts">
                                        <i class="material-symbols-outlined inline-icon">info</i>
                                    </span>

                            </button>
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
            <aside class="col-md-3 d-none d-md-block">
            <section id="popular_tags" class="row mb-3 p-3 bg-white rounded">
                    <h4 class="aside-title fw-bold px-1">Popular Tags</h4>
                    <ol class="ms-2 mb-0">
                        @foreach ($popular_tags as $poptag)
                            <li class="mb-2">
                                <p class="mb-0 blue-hover text-truncate"><a
                                        href="{{ route('tag', ['tag' => $poptag->name]) }}">{{$poptag->name}}</a></p>
                                <p class="mb-0 text-truncate small-grey-text">{{$poptag->num_followers}} Followers</p>
                            </li>
                        @endforeach
                    </ol>
                </section>
            </aside>
        </div>

    </section>
    @auth
        @include('partials.report_post')
    @endauth
    @include('partials.footer')
    @guest
    @include('partials.login_required')
    @endguest
@endsection

@extends('layouts.app')
@section('page-title', $member->username.' | ')
@section('content')
    @include('partials.navbar')
    @push('scripts')
        <script defer src={{ asset('js/ajax.js') }}></script>
        <script defer src = {{ asset('js/contentload.js') }}></script>
        <script defer src={{ asset('js/profile.js') }}></script>

        @auth
        <script defer src={{ asset('js/follow.js') }}></script>
        <script defer src={{ asset('js/follow_tag.js') }}></script>
        <script defer src={{ asset('js/voting.js') }}></script>
        <script defer src={{ asset('js/report.js') }}></script>
        <script defer src={{ asset('js/profile_auth.js') }}></script>
        @endauth
        @guest
        <script defer src = {{ asset('js/login_required.js') }}></script>
        <script defer src={{ asset('js/follow.js') }}></script>
        <script defer src={{ asset('js/follow_tag.js') }}></script>
        @endguest
        <script defer src = {{ asset('js/footer.js') }}></script>
    @endpush
    <section class="container g-0 mx-auto my-4 col-lg-7">
        <section class="profile-widget bg-white rounded mb-3">
            <div class="row g-0">
                <div class="col-sm-12">
                    
                    <row class="d-flex justify-content-end col-12 reportable">
                        @auth
                            @if ($member->isMe(Auth::user()->id))
                                <a class="btn d-flex align-content-center mt-1 me-1" href="{{ route('edit_profile', ['member' => $member->username]) }}">
                                    <span class="btn-outline-blue" style="font-size: 200%;">create</span>
                                </a>
                            @else
                                <button type="button" class="btn d-flex align-content-center mt-1 me-1 report-b report-profile " data-id="{{$member->id}}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#reportProfile">
                                    <span class="btn-outline-red report-b report-profile" data-id="{{$member->id}}" style="font-size: 200%;">flag</span>
                                </button>
                            @endif
                        @endauth
                        @guest
                            <button type="button" class="btn d-flex align-content-center mt-1 me-1 report-b report-profile " data-id="{{$member->id}}>">
                                <span class="btn-outline-red report-b report-profile" data-id="{{$member->id}}" style="font-size: 200%;">flag</span>
                            </button>
                        @endguest
                    </row>
                </div>

                <div class="col-sm-12">
                    <div class="details ">
                        <h3>{{$member->name}}</h3>
                        <h4 class="color-orange fst-italic" id="username" data-id="{{$member->id}}">{{$member->username}}</h4>
                        <p class="bio mb-4 px-3">{{$member->bio}}</p>
                        @auth
                            @if (!$member->isMe(Auth::user()->id))
                                @if ($member->isFollowed(Auth::user()->id))
                                    <button type="button" class="following-button btn btn-outline-primary col-4 mb-3 member-follow-button" data-id="{{$member->username}}"></button>
                                @else
                                    <button type="button" class="follow-button btn btn-outline-primary col-4 mb-3 member-follow-button" data-id="{{$member->username}}"></button>
                                @endif
                            @endif
                        @endauth
                        @guest
                        <button type="button" class="follow-button btn btn-outline-primary col-4 mb-3 member-follow-button" data-id="{{$member->username}}"></button>
                        @endguest
                    </div>
                </div>
                <section class="follow_stats pb-3">
                    <div class="row g-0 d-flex justify-content-around">
                        <div class="col text-center px-2">
                            <button type="button" class="text-button-profile button-following" data-bs-toggle="modal" data-id="{{$member->id}}"
                                    data-bs-target="#modalFollowing">{{$member->following->count()}} Following
                            </button>
                        </div>
                        <div class="col text-center px-2">
                            <button type="button" class="text-button-profile button-followers" data-bs-toggle="modal" data-id="{{$member->id}}"
                                    data-bs-target="#modalFollowers">{{$member->followers->count()}} Followers
                            </button>
                        </div>
                        <div class="col text-center px-2">
                            <button type="button" class="text-button-profile button-tags" data-bs-toggle="modal" data-id="{{$member->id}}"
                                    data-bs-target="#modalFollowedTags">{{$member->tags->count()}} Followed Tags
                            </button>
                        </div>
                    </div>
                    @include('partials.following', ['following' => $member->following])
                    @include('partials.followers', ['followers' => $member->followers])
                    @include('partials.followed_tags', ['tags' => $member->tags])
                </section>
            </div>
        </section>

        <section class="pill-navigation mb-1">
            <ul class="nav nav-pills mb-1 bg-white rounded" id="pills-tab" role="tablist">
                <li class="nav-item col" role="presentation">
                    <button class="nav-link active w-100" id="pills-posts-tab" data-bs-toggle="pill"
                            data-bs-target="#pills-posts" type="button" role="tab" aria-controls="pills-posts"
                            aria-selected="true">Posts
                    </button>
                </li>
                <li class="nav-item col" role="presentation">
                    <button class="nav-link w-100" id="pills-comments-tab" data-bs-toggle="pill"
                            data-bs-target="#pills-comments" type="button" role="tab" aria-controls="pills-comments"
                            aria-selected="false">Comments
                    </button>
                </li>
                @auth
                    @if ($member->isMe(Auth::user()->id))
                        <li class="nav-item col" role="presentation">
                          
                        </li>
                    @endif
                @endauth
            </ul>
        </section>
        <section id="content" class="posts comments-section reportable"></section>
        <div id="spinner" class="d-flex justify-content-center mt-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </section>

@auth
@include('partials.report_comment')
@include('partials.report_post')
@include('partials.report_profile')
@endauth
@guest
@include('partials.login_required')
@endguest
@include('partials.footer')
@endsection

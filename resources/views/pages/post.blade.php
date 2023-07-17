@extends('layouts.app')
@section('page-title', $post->title.' | ')
@section('content')
    @include('partials.navbar')
    @push('scripts')
        <script defer src = {{asset("js/ajax.js")}}></script>
        @auth
        <script defer src = {{asset("js/post.js")}}></script>
        <script defer src = {{asset("js/comments.js")}}></script>
        <script defer src = {{asset("js/voting.js")}}></script>
        <script defer src = {{ asset('js/report.js') }}></script>
        @endauth
        @guest
        <script defer src = {{ asset('js/login_required.js') }}></script>
        @endguest
        <script defer src = {{ asset('js/footer.js') }}></script>
    @endpush
    <section class="container bg-white rounded g-0 mx-auto my-4 col-lg-7"  data-id="{{$post->id}}">
        <section class="news-card mb-3 p-4 posts">
            <header class="row g-0 news-card-header">
                @guest
                <div class="post-voting col-1 d-flex justify-content-center" data-id="{{$post->id}}">
                    <ul class="list-unstyled mb-0">
                        <li>
                            <span class="upvote material-icons-round d-flex justify-content-center">north</span>
                        </li>
                        <li>
                            <span
                                class="downvote material-icons-round d-flex justify-content-center">south</span>
                        </li>
                    </ul>
                </div>
                @endguest

                @auth
                <div class="post-voting col-1 d-flex justify-content-center" data-id="{{$post->id}}">
                    <ul class="list-unstyled mb-0">
                        <li>
                            @if (Auth::user()->hasVotedPost($post->id) != null && Auth::user()->hasVotedPost($post->id)->upvote == 1)
                                <span class="upvote voted material-icons-round d-flex justify-content-center">north</span>
                            @else
                                <span class="upvote material-icons-round d-flex justify-content-center">north</span>
                            @endif
                        </li>
                        <li>
                            <span class="score d-flex justify-content-center" id="score">{{$post->score}}</span>
                        </li>
                        <li>
                            @if (Auth::user()->hasVotedPost($post->id) !== null && Auth::user()->hasVotedPost($post->id)->upvote == false)
                                <span class="downvote voted material-icons-round d-flex justify-content-center">south</span>
                            @else
                                <span class="downvote material-icons-round d-flex justify-content-center">south</span>
                            @endif

                        </li>
                    </ul>
                </div>
                @endauth
                <div class="post-header col me-2">
                    <h1 class="post-title">{{$post->title}}</h1>
                    <h5 class="post-tags">Tags:
                        @foreach ($post->tags as $tag)
                            <a href="{{ route('tag', ['tag' => $tag->name]) }}">{{$tag->name}}</a>;
                        @endforeach
                    </h5>
                    <div class="d-inline">
                        <small class="post-user">Posted by <a
                                href="{{ route('profile', ['member' => $post->owner->username]) }}">{{$post->owner->username}}</a></small>
                        <small>{{$post->get_time()}}</small>
                    </div>
                </div>
            </header>
            <div class="news-card-body " >
                @if ($post->images->count() > 0)
                    <div class="carousel-container">
                        <div id="myCarousel" class="carousel-wrapper offset-lg-1 col-lg-10 carousel slide " data-bs-ride="carousel">
                            @if ($post->images->count() > 1)
                                <div class="carousel-indicators">
                                    @for($index = 0; $index < $post->images->count(); $index++)
                                        @if ($index == 0)
                                            <button type="button" data-bs-target="#carouselExampleIndicators"
                                                    data-bs-slide-to="{{$index}}" class="active" aria-current="true"
                                                    aria-label="Slide {{$index}}"></button>
                                        @else
                                            <button type="button" data-bs-target="#carouselExampleIndicators"
                                                    data-bs-slide-to="{{$index}}" aria-label="Slide {{$index}}"></button>
                                        @endif
                                    @endfor
                                </div>
                            @endif
                            <div class="carousel-inner ">
                                @for($index = 0; $index < $post->images->count(); $index++)
                                    @if ($index == 0)
                                    <div class="carousel-item active">
                                    @else
                                    <div class="carousel-item">
                                    @endif
                                        <img src="{{ URL::asset('/storage/'.$post->id.'/'.$post->images[$index]['file_path']) }}" alt="Post Image" class="d-block">
                                    </div>
                                @endfor
                            </div>
                            @if ($post->images->count() > 1)
                                <button class="carousel-control-prev" type="button" data-bs-target="#myCarousel"
                                        data-bs-slide="prev">
                                    <span class="carousel-control carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#myCarousel"
                                        data-bs-slide="next">
                                    <span class="carousel-control carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                            @endif
                        </div>
                    </div>
                    @endif
                        <div class="card-text mt-3 px-lg-4">{!!$post->body!!}</div>
                    </div>
                    <div class="row g-0 mt-4 news-card-options  reportable" >
                        <a href="#comments" class="col d-flex justify-content-center btn-outline-blue border-end border-2">
                            <span class="material-icons-outlined align-middle me-1">mode_comment</span>
                            <span class="d-none d-md-flex"> {{$post->comments->count()}}</span>
                        </a>
                        @auth
                            @if ($post->owner->isMe(Auth::user()->id))
                                <div class="col d-flex justify-content-center btn-outline-blue dropdown " id="more-horizontal" role="button" data-bs-toggle="dropdown">
                                    <span class="material-icons-round">more_horiz</span>
                                </div>
                                <ul class="dropdown-menu more-horizontal col-1 dropdown-menu-lg-end" aria-labelledby="more-horizontal">
                                    <li><a class="dropdown-item btn-outline-blue" href="{{ route('edit_post', ['newspost' => $post->id]) }}"><span class="material-icons-outlined align-middle">edit</span> <span> Edit</span></a></li>
                                    <li><a id="delete-this" class="dropdown-item btn-outline-red" data-id="{{$post->id}}"><span class="material-icons-outlined align-middle">delete</span> <span> Delete</span></a></li>
                                </ul>
                            @else
                                <div class="col d-flex justify-content-center btn-outline-red report-b report-post" data-bs-toggle="modal" data-bs-target="#reportPost" data-id="{{$post->id}}">
                                    <span class="material-icons-outlined align-middle me-1 report-b report-post" data-id="{{$post->id}}">flag</span>
                                    <span class="d-none d-md-flex report-b report-post" data-id="{{$post->id}}"> Report</span>
                                </div>
                            @endif
                        @endauth

                        @guest
                            <div class="col d-flex justify-content-center btn-outline-red report-b report-post" data-id="{{$post->id}}">
                                <span class="material-icons-outlined align-middle me-1 report-b report-post" data-id="{{$post->id}}">flag</span>
                                <span class="d-none d-md-flex report-b report-post" data-id="{{$post->id}}"> Report</span>
                            </div>
                        @endguest
                    </div>
        </section>

        <section id="comments" class="comments p-2 px-sm-4 mt-3 ">

            <section class="row g-0 mb-4" data-id="{{$post->id}}" id="new-comment-section">
                <div class="md-form amber-textarea active-amber-textarea px-0 ">
                    <textarea class="form-control" id = "comment_content" name="comment" rows="4" placeholder="Leave a comment"></textarea>
                    <button type="button" id = "make_comment_button" class="btn btn-primary mt-2 float-end">Add Comment</button>
                </div>
            </section>

            <section class = "comments-section reportable">
                @foreach ($post->parentComments() as $comment)
                    @include('partials.comment', ['comment' => $comment, 'offset' => 0])
                @endforeach
            </section>
        </section>
    </section>
@auth
@include('partials.report_post')
@include('partials.report_comment')
@endauth
@guest
@include('partials.login_required')
@endguest
    @include('partials.footer')
@endsection

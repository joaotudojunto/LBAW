<?php $mb=2?>
<?php $mt=3?>
@if ($offset!=0)
    <?php $mt=100?>
@endif
@if ($offset>5)
    <?php $offset=5?>
@endif

<div class = "post-comment" data-id = {{$offset + 1}}>
    <div class = "row g-0 offset-{{$offset}} border-start border-bottom border-3 mb-{{$mb}} mt-{{$mt}} comment-{{$comment->id}}" >
        <div class = "d-flex px-3 py-3 ">
            <img class = "flex-shrink-0 rounded-circle" style="width:60px;height:60px;" src="{{ asset('images/defaultavatar.png')}}" alt="Member image">
            <div class = "ms-2 col-10 col-lg-11 comment_box" data-id={{$offset + 1}}>
                <div class = "row justify-content-between g-0">

                    <h5 class="col color-orange"><a href="/member/{{$comment->owner->username}}">{{$comment->owner->username}}</a></h5>

                    <small class="col text-end" style = "color: darkgray;">{{$comment->get_time()}}</small>
                </div>
                <textarea hidden autofocus class = "form-control edit-textarea mb-4" rows="3" >{!!$comment->body!!}</textarea>
                <p class="mb-2 comment_body">{!!$comment->body!!}</p>
                <div class = "d-flex justify-content-end mb-3 edit_button_div post" data-id = {{$comment->id}}>
                    <button type = "button" hidden class="col-4 col-md-3 btn btn-primary edit_button me-3 float-end">Edit</button>
                    <button type = "button" hidden class="col-4 col-md-3 btn btn-danger cancel_button float-end">Cancel</button>
                </div>
                <div class="row comment_options g-0" data-id = {{$comment->id}}>

                    <div class = "col-4 d-flex justify-content-center comment-voting border-end border-2" data-id = {{$comment->id}}>
                        @guest
                            <span class="upvote material-icons-round d-flex justify-content-center">north</span>
                            
                            <span class="downvote material-icons-round d-flex justify-content-center">south</span>
                        @endguest

                        @auth
                            @if ((Auth::user()->hasVotedComment($comment->id) != null) && (Auth::user()->hasVotedComment($comment->id)->upvote == 1))
                                <span class="upvote voted material-icons-round d-flex justify-content-center">north</span>
                            @else
                                <span class="upvote material-icons-round d-flex justify-content-center">north</span>
                            @endif
                            <span class="score d-flex justify-content-center" id="score">{{$comment->score}}</span>
                            @if (Auth::user()->hasVotedComment($comment->id) != null && Auth::user()->hasVotedComment($comment->id)->upvote == 0)
                                <span class="downvote voted material-icons-round d-flex justify-content-center">south</span>
                            @else
                                <span class="downvote material-icons-round d-flex justify-content-center">south</span>
                            @endif
                        @endauth

                    </div>
                    @auth

                    @if (Auth::check() && Auth::user()->id===$comment->id_owner)
                        <div class="col d-flex justify-content-center btn-outline-blue dropdown" id="more-horizontal" role="button" data-bs-toggle="dropdown">
                            <span class="material-icons-round">more_horiz</span>
                        </div>

                        <ul class="dropdown-menu more-horizontal col-1 dropdown-menu-lg-end" aria-labelledby="more-horizontal">
                            <li><a class="dropdown-item btn-outline-blue edit-comment"><span class="material-icons-outlined align-middle edit-comment">edit</span> <span class = "edit-comment"> Edit</span></a></li>
                            <li><a class="dropdown-item btn-outline-red delete-comment"><span class="material-icons-outlined align-middle delete-comment">delete</span> <span class = "delete-comment"> Delete</span></a></li>
                        </ul>

                    @else
                        <div class="col-4 d-flex justify-content-center btn-outline-red report-b report-comment" data-bs-toggle="modal" data-bs-target="#reportComment" data-id={{$comment->id}}>
                            <span class="material-icons-outlined align-middle me-1 report-b report-comment" data-id={{$comment->id}}>flag</span>
                            <span class="d-none d-md-flex report-b report-comment" data-id={{$comment->id}}> Report</span>
                        </div>
                    @endif
                    @endauth
                    @guest
                    <div class="col-4 d-flex justify-content-center btn-outline-red report-b report-comment" data-id={{$comment->id}}>
                        <span class="material-icons-outlined align-middle me-1 report-b report-comment" data-id={{$comment->id}}>flag</span>
                        <span class="d-none d-md-flex report-b report-comment" data-id={{$comment->id}}> Report</span>
                    </div>
                    @endguest
                </div>
            </div>
        </div>
    </div>

</div>

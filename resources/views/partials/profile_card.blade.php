<div class="profile-container d-flex justify-content-between mb-2" data-id={{$member->username}}>
    <div class="d-flex">
        <img src="{{ asset('images/defaultavatar.png')}}" class="flex-shrink-0 rounded-circle"
            style="width:50px;height:50px;" alt="Member avatar">
        <div class="ms-2">
            <h1 class="h5 fw-normal">{{$member->username}}</h1>

        </div>
    </div>
    <div>
    @auth
        @if (!$member->isMe(Auth::user()->id))
            @if ((Auth::user()->isFollowing($member->id)) != null)
                <button type="button" data-id = {{$member->username}}
                    class="following-button btn btn-outline-primary col-12 mb-1 member-follow-button">
                </button>
            @else
                <button type="button" data-id = {{$member->username}}
                    class="follow-button btn btn-outline-primary col-12 mb-1 member-follow-button">
                </button>
            @endif
        @endif
    @endauth
    @guest
    <button type="button" data-id = {{$member->username}}
        class="follow-button btn btn-outline-primary col-12 mb-1 member-follow-button">
    </button>
    @endguest

    </div>
</div>

<div class="profile-container d-flex justify-content-between mb-2">
    <div class="d-flex">
        <img src="{{ URL::asset('/storage/tag/'.$tag->id.'.png') }}" class="flex-shrink-0 rounded-circle"
            style="width:50px;height:50px;" alt="Tag image">
        <div class="ms-2">
            <h1 class="h5 fw-normal"><a href="{{ route('tag', ['tag' => $tag->name]) }}">{{$tag->name}}</a></h1>
            <p class="h6 fw-normal" id="tag_followers" data_id={{$tag->id}}>{{$tag->followers->count()}} Followers</p>
        </div>
    </div>
    <div>
        @auth
        @if (($tag->isFollowed(Auth::user()->id)) != null)
            <button type="button" class="following-button btn btn-outline-primary col-12 mb-1 tag-follow-button"  data-id = {{$tag->id}} ></button>
        @else
            <button type="button" class="follow-button btn btn-outline-primary col-12 mb-1 tag-follow-button"  data-id = {{$tag->id}} ></button>
        @endif
        @endauth
        @guest
        <button type="button" class="follow-button btn btn-outline-primary col-12 mb-1 tag-follow-button"  data-id = {{$tag->id}} ></button>
        @endguest
    </div>
</div>

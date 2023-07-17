<div class="row g-0 p-3 mb-2 rounded bg-white align-items-center">
    <div class="col-2 col-xl-1 pe-3">
        <img src="{{ URL::asset('/storage/tag/'.$tag->id.'.png') }}" alt="tag image" class="rounded-circle img-fluid img-responsive" style="width:90px;height:auto;">
    </div>
    <div class="col-6 col-xl-9">
        <div class="row g-0">
            <h3 class="h3 truncate"><a href="{{ route('tag', ['tag' => $tag->name]) }}">{{$tag->name}}</a></h3>
        </div>
    </div>
    <div class="col-4 col-xl-2">
        <div class="row g-0 d-flex justify-content-end">
            @auth
                @if (($tag->isFollowed(Auth::user()->id)) != null)
                    <button type="button" class="following-button btn btn-outline-primary col-12 mb-1 tag-following-button"  data-id = {{$tag->id}} ></button>
                @else
                    <button type="button" class="follow-button btn btn-outline-primary col-12 mb-1 tag-follow-button"  data-id = {{$tag->id}} ></button>
                @endif
            @endauth
            @guest
            <button type="button" class="follow-button btn btn-outline-primary col-12 mb-1 tag-follow-button"  data-id = {{$tag->id}} ></button>
            @endguest
        </div>
        <div class="row g-0">
            <p class="m-0 d-flex justify-content-end" id="tag_followers" data-id={{$tag->id}}>{{$tag->followers->count()}} Followers</p>
        </div>
    </div>
</div>

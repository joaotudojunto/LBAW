<div class="modal fade" id="modalFollowedTags" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Followed Tags</h5>
                <button type="button" data-bs-dismiss="modal" id="close-window-button" aria-label="Close"><span
                        class="material-icons-round" id="downvote">close</span></button>
            </div>
            <div class="modal-body">
                <div class="profiles-container container-tag">
                    @foreach ($tags as $tag)
                        @include('partials.tag_card',['tag'=>$tag])
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="loginRequired" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabelLogin">Login Required</h5>
                <button type="button" data-bs-dismiss="modal" id="close-window-button" aria-label="Close"><span
                        class="material-icons-round" id="downvote">close</span></button>
            </div>
            <div class="modal-body">
            
                <p class="pop-up-instruction">To continue with this action please login with your ActiveNews account.</p>
            </div>
            <div class="modal-footer">
                <button type="button" id="save-button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="{{ route('login') }}">
                    <button type="button" id="delete-button" class="btn btn-primary">Go to Login Page</button>
                </a>
            </div>
        </div>
    </div>
</div>

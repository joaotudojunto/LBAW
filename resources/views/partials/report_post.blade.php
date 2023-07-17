<div class="modal fade" id="reportPost"  data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabelReport">Report Post</h5>
                <button type="button" data-bs-dismiss="modal" id= "close-window-button" aria-label="Close"><span class="material-icons-round" id="downvote">close</span></button>
            </div>
            <form class="reportForm">
                <div class="modal-body">
                    <div class="form-check" >
                        <div>
                            <input class="form-check-input" type="radio" name="option" id="postRadios1"  value="This is spam" >
                            <label class="form-check-label" for="postRadios1">
                                This is spam
                            </label>
                        </div>
                        <div>
                            <input class="form-check-input" type="radio" name="option" id="postRadios2"  value="This is misinformation" >
                            <label class="form-check-label" for="postRadios2">
                                This is misinformation
                            </label>
                        </div>
                        <div>
                            <input class="form-check-input" type="radio" name="option" id="postRadios3"  value="This is abusive or harassing" >
                            <label class="form-check-label" for="postRadios3">
                                This is abusive or harassing
                            </label>
                        </div>
                        <div>
                            <input class="form-check-input" type="radio" name="option" id="postRadios4"  value="Other issues" >
                            <label class="form-check-label" for="postRadios4">
                                Other issues
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" id="postReport">
                    <button type="button" class="btn btn-secondary cancel-button " data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger report-submit" >Report</button>
                </div>
            </form>
        </div>

    </div>
</div>

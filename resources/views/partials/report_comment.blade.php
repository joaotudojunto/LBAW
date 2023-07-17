
<div class="modal fade" id="reportComment"  data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="staticBackdropLabelReport">Report Comment</h5>
                <button type="button" data-bs-dismiss="modal" id= "close-window-button" aria-label="Close"><span class="material-icons-round" id="downvote">close</span></button>
            </div>
            <form class="reportForm">
                <div class="modal-body">
                    <div class="form-check" >
                        <div>
                            <input class="form-check-input" type="radio" name="option" id="commentRadios1" value="This is spam" >
                            <label class="form-check-label" for="commentRadios1">
                                This is spam
                            </label>
                        </div>
                        <div>
                            <input class="form-check-input" type="radio" name="option" id="commentRadios2" value="This is misinformation" >
                            <label class="form-check-label" for="commentRadios2">
                                This is misinformation
                            </label>
                        </div>
                        <div>
                            <input class="form-check-input" type="radio" name="option" id="commentRadios3" value="This is abusive or harassing" >
                            <label class="form-check-label" for="commentRadios3">
                                This is abusive or harassing
                            </label>
                        </div>
                        <div>
                            <input class="form-check-input" type="radio" name="option" id="commentRadios4" value="Other issues" >
                            <label class="form-check-label" for="commentRadios4">
                                Other issues
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" id="commentReport" >
                    <button type="button" class="btn btn-secondary cancel-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger report-submit" data-bs-dismiss="modal">Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

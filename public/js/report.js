const reports = document.querySelectorAll(".reportable");
const report_submits = document.querySelectorAll(".report-submit")

let memberId;
let postId;
let tagId;
let commentId;

reports.forEach(report => {
    report.addEventListener("click", function (event) {

        let report_b = event.target.closest(".report-b");
        if (report_b != null) {
            let profile_report = event.target.closest(".report-profile");
            if (profile_report != null) {
                selectProfile(profile_report);
                return;
            }

            let comment_report = event.target.closest(".report-comment");
            if (comment_report != null) {
                selectComment(comment_report);
                return;
            }

            let post_report = event.target.closest(".report-post");
            if (post_report != null) {
                selectPost(post_report);
                return;
            }
        }
    })
})

report_submits.forEach(report_submit => {
    report_submit.addEventListener("click", function (event) {
        let profile_report = event.target.closest("#profileReport");
        if (profile_report != null) {
            reportProfile(profile_report);
            return;
        }


        let comment_report = event.target.closest("#commentReport");

        if (comment_report != null) {
            reportComment(comment_report);
            return;
        }

        let post_report = event.target.closest("#postReport");

        if (post_report != null) {

            reportPost(post_report);

            return;
        }
    })
})


function selectPost(post) {
    post_id = post.getAttribute("data-id");
}

function selectProfile(profile) {
    member_id = profile.getAttribute("data-id");
}

function selectComment(comment) {
    comment_id = comment.getAttribute("data-id");
}

function reportProfile(profile_report) {
    const report_form = profile_report.closest(".reportForm");
    const route = "/api/member/" + member_id + "/report";
    let option = report_form.querySelector('input[name="option"]:checked');
    let content = report_form.querySelector('input[name="option"]:checked').value;
    let closeModal = bootstrap.Modal.getInstance(profile_report.closest(".modal"));
    closeModal.toggle();
    option.checked = false;
    const data = {report: content}

    sendAjaxRequest("POST", route, data, successfulReport, failedReport)
}


function reportComment(comment_report) {
    const report_form = comment_report.closest(".reportForm");
    const route = "/api/comment/" + comment_id + "/report";
    let option = report_form.querySelector('input[name="option"]:checked');
    let content = report_form.querySelector('input[name="option"]:checked').value;
    let closeModal = bootstrap.Modal.getInstance(comment_report.closest(".modal"));
    closeModal.toggle();
    option.checked = false;
    const data = {report: content}
    sendAjaxRequest("POST", route, data, successfulReport, failedReport)
}

function reportPost(post_report) {
    const report_form = post_report.closest(".reportForm");
    const route = "/api/post/" + post_id + "/report"
    let option = report_form.querySelector('input[name="option"]:checked');
    let content = report_form.querySelector('input[name="option"]:checked').value;
    let closeModal = bootstrap.Modal.getInstance(post_report.closest(".modal"));
    closeModal.toggle();
    option.checked = false;
    const data = {report: content}
    sendAjaxRequest("POST", route, data,successfulReport, failedReport)
}


function successfulReport(){
    createToast("Report successfully submitted.",true);
}
function failedReport(){
    createToast("Report failed to submit.",false);
}

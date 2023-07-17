// Comments
const make_comment_button = document.querySelector("#make_comment_button")
let post_id = -1
if (document.getElementById("new-comment-section") !== null)
    post_id = document.getElementById("new-comment-section").getAttribute("data-id");
const comment = document.getElementById("comment_content")

// Replies
let comment_id = null

if (make_comment_button != null) {
    make_comment_button.addEventListener("click", function(event) {
        event.preventDefault()
        const content = {comment: comment.value}
        if (comment.value == "") return
        const route = "/api/post/" + post_id + "/comment"
        sendAjaxRequest("POST", route, content, loadComment, loadError)

    })
}

function loadComment(response) {
    const json_data = JSON.parse(response)
    const comments_section = document.querySelector(".comments-section")
    comment.value = null;
    comments_section.innerHTML = json_data + comments_section.innerHTML
}

document.querySelector(".comments-section").addEventListener("click", function (event) {
    let classList = event.target.classList
    let isPostPage = event.target.closest("div.post-comment")

    

    if (classList.contains("upvote")) {
        let id_comment = event.target.closest(".comment-voting").getAttribute("data-id")
        const route = "/api/comment/" + id_comment + "/vote"

        sendAjaxRequest("POST", route, {vote: true}, upvoteResponse.bind(event.target.closest(".comment-voting")), loadError)
    }

    else if (classList.contains("downvote")) {
        let id_comment = event.target.closest(".comment-voting").getAttribute("data-id")
        const route = "/api/comment/" + id_comment + "/vote"

        sendAjaxRequest("POST", route, {vote: false}, downvoteResponse.bind(event.target.closest(".comment-voting")), loadError)
    }

    else if (isPostPage === null) return

    else if (classList.contains("delete-comment")) {
        let id_comment = event.target.closest(".comment_options").getAttribute("data-id")
        const route = "/api/comment/" + id_comment
        sendAjaxRequest("DELETE", route, null, deleteComment.bind(event.target.closest(".post-comment")), loadError)
    }

    // if (isPostPage === null) return

    else if (classList.contains("edit-comment")) {
        let textarea = event.target.closest(".comment_box").querySelector(".edit-textarea")
        event.target.closest(".comment_box").querySelector(".dropdown-menu").hidden = true
        event.target.closest(".comment_box").querySelector(".edit_button").hidden = false
        event.target.closest(".comment_box").querySelector(".cancel_button").hidden = false
        event.target.closest(".comment_box").querySelector(".comment_options").hidden = true
        textarea.hidden = false
        textarea.focus()
        event.target.closest(".comment_box").querySelector(".comment_body").hidden = true
    }

    else if (classList.contains("cancel_button")) {
        let textarea = event.target.closest(".comment_box").querySelector(".edit-textarea")
        event.target.closest(".comment_box").querySelector(".dropdown-menu").hidden = false
        event.target.closest(".comment_box").querySelector(".edit_button").hidden = true
        event.target.closest(".comment_box").querySelector(".cancel_button").hidden = true
        textarea.hidden = true
        event.target.closest(".comment_box").querySelector(".comment_body").hidden = false
        event.target.closest(".comment_box").querySelector(".comment_options").hidden = false
    }


    else if (classList.contains("edit_button")) {
        let textarea = event.target.closest(".comment_box").querySelector(".edit-textarea")
        let id_comment = event.target.closest(".edit_button_div").getAttribute("data-id")
        const route = "/api/comment/" + id_comment
        sendAjaxRequest("PATCH", route, {body: textarea.value}, loadReply, loadError)
    }
})

function deleteComment(response) {
    this.remove()
    createToast("Successfully deleted comment", true)
}



function addComment(response){
    let parent = this.closest('.post-comment').querySelector(".replies")
    let offset = parent.getAttribute("data-id")
    let data = JSON.parse(response)['html']

    parent.innerHTML =  parent.innerHTML + data

}


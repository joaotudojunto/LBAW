let voting = document.querySelector(".posts");
voting.addEventListener("click", function(event){
    let post_voting = event.target.closest(".post-voting")
    let id_post = null
    if (post_voting === null) return
    id_post = post_voting.getAttribute("data-id")
    const route = "/api/post/" + id_post + "/vote"
    let request = null

    if (event.target.classList.contains("upvote")) {
        request = {vote: true}
        sendAjaxRequest("POST", route, request, upvoteResponse.bind(post_voting), loadError)
    }

    else if (event.target.classList.contains("downvote")) {
        request = {vote: false}
        sendAjaxRequest("POST", route, request, downvoteResponse.bind(post_voting), loadError)
    }
});

function upvoteResponse (response) {
    const json_data = JSON.parse(response)
    const vote = json_data['votes']
    let score = this.querySelector(".score")
    let upvote = this.querySelector(".upvote")
    let downvote = this.querySelector(".downvote")

    if (upvote.classList.contains("voted")) {
        upvote.classList.remove("voted");
    }

    else {
        if (downvote.classList.contains("voted")) {
            downvote.classList.remove("voted");
        }
        upvote.classList.add("voted");
    }
    score.textContent = vote;
    
}


function downvoteResponse(response) {
    const json_data = JSON.parse(response)
    const vote  = json_data['votes']
    let score = this.querySelector(".score")
    let upvote = this.querySelector(".upvote")
    let downvote = this.querySelector(".downvote")

    if (downvote.classList.contains("voted")) {
        downvote.classList.remove("voted");
    }

    else {
        if (upvote.classList.contains("voted")) {
            upvote.classList.remove("voted");
        }
        downvote.classList.add("voted");
    }
    score.textContent = vote;
}

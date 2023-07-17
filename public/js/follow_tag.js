document.querySelectorAll(".container-tag").forEach((element) => {
    element.addEventListener("click", function(event) {
        let classList = event.target.classList
        if (classList.contains("follow-button")) {
            tagFollowHandler(event.target)
        }

        if (classList.contains("following-button")) {
            tagUnfollowHandler(event.target)
        }
    });

});

let tag_info_page = document.querySelector("#tag-info")
if (tag_info_page != null){
    tag_info_page.addEventListener("click", function(event) {
        let classList = event.target.classList
        if (classList.contains("follow-button")) {
            tagFollowHandler(event.target)
        }

        if (classList.contains("following-button")) {
            tagUnfollowHandler(event.target)
        }
    });
}


function tagFollowHandler(button) {
    const tag_follow_button = button.getAttribute("data-id");
    let followed_tags_count= document.querySelector(".button-tags");
    const route = '/api/tag/' + tag_follow_button + '/follow';

    if (followed_tags_count != null){
        request = {userProfile: followed_tags_count.getAttribute("data-id")};
    }
    else{
        request = {userProfile: null};
    }

    sendAjaxRequest("POST", route, request, (response) => {
        const json_data = JSON.parse(response);
        const followers = json_data['followers']
        const followedtags = json_data['followedtags']

        if (button.classList.contains("follow-button")) {
            button.classList.remove("follow-button");
            button.classList.add("following-button");
        }

        document.querySelectorAll("#tag_followers").forEach((element) => {
            if (element.getAttribute("data_id") == tag_follow_button){
                element.innerHTML = followers + " Followers";
            }
        });

        if (followed_tags_count != null){
            followed_tags_count.innerHTML = followedtags + " Followed tags";
        }
    });
}

function tagUnfollowHandler(button) {
    const tag_follow_button = button.getAttribute("data-id");
    let followed_tags_count= document.querySelector(".button-tags");
    const route = '/api/tag/' + tag_follow_button + '/follow';

    if (followed_tags_count != null){
        request = {userProfile: followed_tags_count.getAttribute("data-id")};
    }
    else{
        request = {userProfile: null};
    }

    sendAjaxRequest("DELETE", route, request, (response) => {
        const json_data = JSON.parse(response);
        const followers = json_data['followers']
        const followedtags = json_data['followedtags']

        if (button.classList.contains("following-button")) {
            button.classList.remove("following-button");
            button.classList.add("follow-button");
        }

        document.querySelectorAll("#tag_followers").forEach((element) => {
            if (element.getAttribute("data_id") == tag_follow_button){
                element.innerHTML = followers + " Followers";
            }
        });

        if (followed_tags_count != null){
            followed_tags_count.innerHTML = followedtags + " Followed tags";
        }
    });
}

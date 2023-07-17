document.querySelector("#content").addEventListener("click", function(event) {
    let classList = event.target.classList
    if (classList.contains("member-follow-button")) {
        memberSearchFollowHandler(event.target)
    }

    if (classList.contains("member-following-button")) {
        memberSearchUnfollowHandler(event.target)
    }

    if (classList.contains("tag-follow-button")) {
        tagSearchFollowHandler(event.target)
    }

    if (classList.contains("tag-following-button")) {
        tagSearchUnfollowHandler(event.target)
    }
})



function tagSearchFollowHandler(button) {
    const tag_follow_button = button.getAttribute("data-id");
    const route = '/api/tag/' + tag_follow_button + '/follow';
    let request = {};

    request = {userProfile: null};

    sendAjaxRequest("POST", route, request, (response) => {
        const json_data = JSON.parse(response);
        const followers = json_data['followers']

        if (button.classList.contains("tag-follow-button")) {
            button.classList.remove("follow-button");
            button.classList.remove("tag-follow-button");
            button.classList.add("following-button");
            button.classList.add("tag-following-button");
        }

        let tag_followers = document.querySelectorAll("#tag_followers")

        tag_followers.forEach(follower_indicator => {
            if (follower_indicator.getAttribute("data-id") == tag_follow_button){
                follower_indicator.innerHTML = followers + " Followers";
            }
        })
    }, loadError);
}

function tagSearchUnfollowHandler(button) {
    const tag_follow_button = button.getAttribute("data-id");
    const route = '/api/tag/' + tag_follow_button + '/follow';
    let request = {};

    request = {userProfile: null};

    sendAjaxRequest("DELETE", route, request, (response) => {
        const json_data = JSON.parse(response);
        const followers = json_data['followers']

        if (button.classList.contains("tag-following-button")) {
            button.classList.remove("following-button");
            button.classList.remove("tag-following-button");
            button.classList.add("follow-button");
            button.classList.add("tag-follow-button");
        }

        let tag_followers = document.querySelectorAll("#tag_followers")

        tag_followers.forEach(follower_indicator => {
            if (follower_indicator.getAttribute("data-id") == tag_follow_button){
                follower_indicator.innerHTML = followers + " Followers";
            }
        })
    }, loadError);
}


function memberSearchFollowHandler(button) {
    const username_follow_button = button.getAttribute("data-id");
    const member_id = document.querySelector("#member-name-search").getAttribute("data-id")
    const route = "/api/member/" + username_follow_button + "/follow";
    let request = {};
    request = {userProfile: member_id};

    sendAjaxRequest("POST", route, request, (response) => {
        const json_data = JSON.parse(response);
        const followers = json_data['followers'];

        if (button.classList.contains("member-follow-button")) {
            button.classList.remove("member-follow-button");
            button.classList.remove("follow-button");
            button.classList.add("member-following-button");
            button.classList.add("following-button");
        }

        let member_followers = document.querySelectorAll("#member_followers")

        member_followers.forEach(follower_indicator => {
            if (follower_indicator.getAttribute("data-id") == username_follow_button){
                follower_indicator.innerHTML = followers + " Followers";
            }
        })
    }, loadError);
}

function memberSearchUnfollowHandler(button) {
    const username_follow_button = button.getAttribute("data-id");
    const member_id = document.querySelector("#member-name-search").getAttribute("data-id")
    const route = "/api/member/" + username_follow_button + "/follow";
    let request = {};
    request = {userProfile: member_id};

    sendAjaxRequest("DELETE", route, request, (response) => {
        const json_data = JSON.parse(response);
        const followers = json_data['followers'];

        if (button.classList.contains("member-following-button")) {
            button.classList.remove("member-following-button");
            button.classList.remove("following-button");
            button.classList.add("member-follow-button");
            button.classList.add("follow-button")
        }

        let member_followers = document.querySelectorAll("#member_followers")

        member_followers.forEach(follower_indicator => {
            if (follower_indicator.getAttribute("data-id") == username_follow_button){
                follower_indicator.innerHTML = followers + " Followers";
            }
        })
    }, loadError);
}

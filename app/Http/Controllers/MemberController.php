<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Notifications\FollowNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MemberController extends Controller{
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Member  $member
     * @return \Illuminate\Http\Response
     */
    public function show(Member $member)
    {
        return view('pages.profile', ['member' => $member]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Member $member)
    {
        if (!Auth::check()) return redirect('login');
        $this->authorize('owner', $member);

        return view('pages.edit_profile', ['member' => $member]);
    }


    private function create_avatar_image($file, $member)
    {
        $path = 'public/members/'.$member->id;
        if (!File::exists($path)) {
            Storage::makeDirectory($path);
        }

        if ($member->avatar_image !== "default_avatar.png" ) {
            Storage::delete('public/members/'.$member->avatar_image);
        }

        $file->store($path);
        return $file->hashName();
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Member  $member
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Member $member)
    {
        if (!Auth::check()) return redirect('login');
        $this->authorize('owner', $member);

        $member->username = $request->input("username");

        $member->save();

        return redirect(route('profile', ['member' => $member->username]));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function settings(Member $member) {
        if (!Auth::check()) return redirect('login');
        $this->authorize('owner', $member);

        return view('pages.settings', ['member' => $member]);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function change_email_validator(array $data)
    {
        return Validator::make($data, [
            'email' => ['required', 'string', 'email', 'unique:member'],
            'email_confirmation' => ['required', 'string', 'email', 'same:email'],
            'password' => ['required', 'string', 'min:4']
        ]);
    }

    public function change_email(Request $request) {
        if (!Auth::check()) return response()->json(array('auth' => 'Forbidden Access'), 403);

        $validator = $this->change_email_validator($request->all());
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $member = Auth::user();
        if (!Hash::check($request->input('password'), $member->password)) {
            return response()->json(array('password' => 'Incorrect Password'), 400);
        }

        $member->email = $request->input("email");
        $member->save();
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function change_password_validator(array $data)
    {
        return Validator::make($data, [
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:4', 'confirmed'],
            'new_password_confirmation' => ['required', 'string', 'min:4', 'same:new_password']
        ]);
    }

    public function change_password(Request $request) {
        if (!Auth::check()) return response()->json(array('auth' => 'Forbidden Access'), 403);

        $validator = $this->change_password_validator($request->all());
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $member = Auth::user();

        if (!Hash::check($request->input('old_password'), $member->password)) {
            return response()->json(array('old_password' => 'Incorrect Password'), 400);
        }

        $member->password = Hash::make($request->input("new_password"));
        $member->save();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Member  $member
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Member $member)
    {
        if (!Auth::check()) return response()->json('Forbidden Access', 403);
        $this->authorize('delete', $member);

        if ($request->has('password')) {
            if (!Hash::check($request->input('password'), $member->password)) {
                return response()->json('Incorrect Password', 400);
            }

            Auth::logout();
        }

        Storage::deleteDirectory('public/members/'.$member->id);

        $member->delete();
    }

    public function content(Request $request, Member $member, $content)
    {
        if (!$request->has('page')) {
            return response()->json('No page provided', 400);
        }
        $page = $request->input('page');

        switch ($content) {
            case "posts":
                $data = $member->posts()->orderBy('date_time', 'desc')->forPage($page)->get();
                $type = 'post';
                break;
            case "comments":
                $data = $member->comments()->orderBy('date_time', 'desc')->forPage($page)->get();
                $type = 'comment';
                break;
            default:
                return response()->json('Invalid content filter', 400);
        }

        if (count($data) > 0) {
            $html = [];
            foreach ($data as $element) {
                array_push($html, view('partials.' . $type . 'card', [$type => $element])->render());
            }

            return response()->json($html);
        }

        return response()->json([view('partials.nocontent')->render()]);
    }


    public function search(Request $request) {
        if ($request->has('query') && $request->has('page')) {
            $members = Member::search_members($request->input('query'), $request->input('page'));

            if (count($members) > 0) {
                $html = [];
                foreach($members as $member){
                    array_push($html, view('partials.membercard', ['member' => $member])->render());
                }
                return response()->json($html);
            }
        }

        return response()->json([view('partials.nocontent')->render()]);
    }

    public function follow(Request $request, Member $member)
    {
        if (!Auth::check()) return response()->json(array('auth' => 'Forbidden Access'), 403);
        $followingMember = Member::find(Auth::user()->id);
        $followedMember = Member::find($member->id);

        $follow = $followedMember->isFollowed(Auth::user()->id);

        if ($follow === null) {
            Auth::user()->follow_member($member->id);
            $followedMember->notify(new FollowNotification($followingMember->username));
        }

        if ($request->input('userProfile') !== null){
            $id_page = $request->input('userProfile');
            $pageMember = Member::find($id_page);
            return response()->json(array('followers' => $pageMember->followers->count(), 'following' => $pageMember->following->count()));
        }
    }


    public function unfollow(Request $request, Member $member)
    {
        if (!Auth::check()) return response()->json(array('auth' => 'Forbidden Access'), 403);
        $followingMember = Member::find(Auth::user()->id);
        $followedMember = Member::find($member->id);

        $follow = $followedMember->isFollowed(Auth::user()->id);

        if ($follow !== null) {
            Auth::user()->unfollow_member($member->id);
        }

        if ($request->input('userProfile') !== null){
            $id_page = $request->input('userProfile');
            $pageMember = Member::find($id_page);
            return response()->json(array('followers' => $pageMember->followers->count(), 'following' => $pageMember->following->count()));
        }
    }

    public function getFollowingModal(Member $member)
    {
        $htmlFollowing = [];
        foreach ($member->following as $follow) {
            array_push($htmlFollowing, view('partials.profile_card', ['member' => $follow])->render());
        }
        return response()->json($htmlFollowing);
    }

    public function getFollowersModal(Member $member)
    {
        $htmlFollowers = [];
        foreach ($member->followers as $follower) {
            array_push($htmlFollowers, view('partials.profile_card', ['member' => $follower])->render());
        }
        return response()->json($htmlFollowers);
    }

    public function getFollowedTagsModal(Member $member)
    {
        $htmlFollowedTags = [];
        foreach ($member->tags as $tag) {
            array_push($htmlFollowedtags, view('partials.tag_card', ['tag' => $tag])->render());
        }
        return response()->json($htmlFollowedtags);
    }


    public function report(Request $request, Member $member)
    {
        if (!Auth::check()) return response()->json(array('auth' => 'Forbidden Access'), 403);
        Auth::user()->add_member_report($member->id, $request->input('report'));
    }

    public function dismiss(Member $member)
    {
        if (!Auth::check() || !Auth::user()->admin) return response()->json(array('auth' => 'Forbidden Access'), 403);
        $member->dismiss_member_report();
    }
}

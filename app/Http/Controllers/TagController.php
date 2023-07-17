<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\NewsPost;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TagController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param \App\Models\Tag $tag
     * @return \Illuminate\Http\Response
     */
    public function show(Tag $tag)
    {
        return view('pages.tag', ['tag' => $tag]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Tag $tag
     * @return \Illuminate\Http\Response
     */
    public function destroy(Tag $tag)
    {
        if (!Auth::check()) return response()->json('Forbidden access', 403);
        $tag->delete();
    }

    public function content(Request $request, Tag $tag, $content)
    {
        if (!$request->has('page')) {
            return response()->json('No page provided', 400);
        }
        $page = $request->input('page');

        switch ($content) {
            case "trending":
                $data = $this->trending($tag->id, $page);
                break;
            case "latest":
                $data = $tag->posts()->orderBy('date_time', 'desc')->forPage($page)->get();
                break;
            default:
                return response()->json('Invalid content filter', 400);
        }

        if (count($data) > 0) {
            $html = [];
            foreach ($data as $element) {
                array_push($html, view('partials.postcard', ['post' => $element])->render());
            }

            return response()->json($html);
        }

        return response()->json([view('partials.nocontent')->render()]);
    }

    private function trending($id_tag, $page)
    {
        $feed = [];
        $num_rows = ($page - 1) * 15;

        $aux = Tag::tag_trending_posts($id_tag, $num_rows);

        foreach ($aux as $auxIds) {
            array_push($feed, NewsPost::find($auxIds->id));
        }
        return $feed;
    }

    public function search(Request $request)
    {
        if ($request->has('query') && $request->has('page')) {
            $tags = Tag::search_tags($request->input('query'), $request->input('page'));

            if (count($tags) > 0) {
                $html = [];
                foreach ($tags as $tag) {
                    array_push($html, view('partials.tagcard', ['tag' => $tag])->render());
                }
                return response()->json($html);
            }
        }

        return response()->json([view('partials.nocontent')->render()]);
    }

    public function follow(Request $request, tag $tag)
    {
        if (!Auth::check()) return response()->json(array('auth' => 'Forbidden Access'), 403);

        $follow = $tag->isFollowed(Auth::user()->id);

        if ($follow === null) {
            Auth::user()->follow_tag($tag->id);
        }

        if ($request->input('userProfile') !== 'null') {
            $member = Member::find($request->input('userProfile'));
            return response()->json(array('followers' => $tag->followers->count(), 'followedtags' => $member->tags->count()));
        } else {
            return response()->json(array('followers' => $tag->followers->count(), 'followedtags' => null));
        }

    }

    public function unfollow(Request $request, Tag $tag)
    {
        if (!Auth::check()) return response()->json(array('auth' => 'Forbidden Access'), 403);

        $follow = $tag->isFollowed(Auth::user()->id);

        if ($follow !== null) {
            Auth::user()->unfollow_tag($tag->id);
        }

        if ($request->input('userProfile') !== 'null') {
            $member = Member::find($request->input('userProfile'));
            return response()->json(array('followers' => $tag->followers->count(), 'followedtags' => $member->tags->count()));
        } else {
            return response()->json(array('followers' => $tag->followers->count(), 'followedtags' => null));
        }
    }


    public function report(Request $request, Tag $tag)
    {
        if (!Auth::check()) return response()->json(array('auth' => 'Forbidden Access'), 403);
        Auth::user()->report_tag($tag->id, $request->input('report'));
    }

    public function dismiss(Tag $tag)
    {
        if (!Auth::check() || !Auth::user()->admin) return response()->json(array('auth' => 'Forbidden Access'), 403);
        $tag->dismiss_report();
    }
}

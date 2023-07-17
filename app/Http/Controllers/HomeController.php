<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\NewsPost;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Show the application home
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function show()
    {
        $popular_tags = Tag::popular_tags();

        return view('pages.home', ['popular_tags' => $popular_tags]);
    }

    public function content(Request $request, $content)
    {
        if (!$request->has('page')) {
            return response()->json('No page provided', 400);
        }
        $page = $request->input('page');

        switch ($content) {
            case "feed":
                if (!Auth::check()) return response()->json(array('auth' => 'Forbidden Access'), 403);
                $data = $this->feed($page);
                break;
            case "trending":
                $data = $this->trending($page);
                break;
            case "latest":
                $data = NewsPost::orderBy('date_time', 'desc')->forPage($page)->get();
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

    private function feed($page)
    {
        $feed = [];
        $num_rows = $page * 15;
        $aux = NewsPost::feed($num_rows);

        foreach($aux as $auxIds){
            array_push($feed,NewsPost::find($auxIds->id));
        }
        return $feed;

    }
    private function trending($page)
    {
        $feed = [];
        $num_rows = ($page-1) * 15;
        $aux = NewsPost::trending_posts($num_rows);

        foreach($aux as $auxIds ){
            array_push($feed,NewsPost::find($auxIds->id));
        }
        return $feed;
    }
}

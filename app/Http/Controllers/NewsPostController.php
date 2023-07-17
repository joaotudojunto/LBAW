<?php

namespace App\Http\Controllers;

use App\Models\NewsPost;
use App\Models\Member;
use App\Models\PostReport;
use App\Models\Tag;
use Dotenv\Exception\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class NewsPostController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!Auth::check()) return redirect('login');

        $tags = Tag::orderBy('name', 'asc')->get();
        return view('pages.create_post', ['tags' => $tags]);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function post_validator(array $data)
    {
        return Validator::make($data, [
            'title' => ['required', 'string'],
            'body' => ['nullable', 'string'],
            'tags' => ['required', 'array', 'between:1,10'],
            'tags.*' => ['string'],
            'images' => ['array', 'max:10'],
            'images.*' => ['image']
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        if (!Auth::check()) return redirect('login');

        $validator = $this->post_validator($request->all());
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Initiate new post store transaction
        DB::beginTransaction();

        // Create a NewsPost
        try {
            $newspost = new NewsPost;

            $newspost->id_owner = Auth::user()->id;
            $newspost->title = $request->input('title');
            if ($request->has('body')) {
                $newspost->body = $request->input('body');
            }

            $newspost->save();
        } catch (ValidationException $e) {
            DB::rollBack();
            return back()->withErrors(['dberror' => $e->getMessage()])->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        // Get post id after inserting
        $id_post = $newspost->id;

        // Insert all Post tags
        foreach ($request->input('tags') as $name) {
            try {
                DB::table('tag')->insertOrIgnore([['name' => $name]]);

                $id_tag = Tag::firstWhere('name', $name)->id;
                DB::table('post_tag')->insert(['id_post' => $id_post, 'id_tag' => $id_tag]);
            } catch (ValidationException $e) {
                DB::rollBack();
                return back()->withErrors(['dberror' => $e->getMessage()])->withInput();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }

        // Insert Post Images
        if ($request->hasFile('images')) {
            $images = $request->file('images');

            $path = 'public/' . $id_post;
            Storage::makeDirectory($path);

            foreach ($images as $image) {
                try {
                    DB::table('post_image')->insert(['id_post' => $id_post, 'file_path' => $image->hashName()]);
                    $image->store($path);
                } catch (ValidationException $e) {
                    DB::rollBack();
                    Storage::deleteDirectory($path);
                    return back()->withErrors(['dberror' => $e->getMessage()])->withInput();
                } catch (\Exception $e) {
                    DB::rollBack();
                    Storage::deleteDirectory($path);
                    throw $e;
                }
            }
        }

        DB::commit();

        return redirect(route('post', ['newspost' => $id_post]));
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\NewsPost $newsPost
     * @return \Illuminate\Http\Response
     */
    public function show(NewsPost $newspost)
    {
        return view('pages.post', ['post' => $newspost]);
    }


    public function vote(Request $request, NewsPost $newspost)
    {
        if (!Auth::check()) return response()->json(array('auth' => 'Forbidden Access'), 403);

        $vote = Auth::user()->hasVotedPost($newspost->id);
        if ($vote === null) {
            Auth::user()->add_post_vote($request->input('vote'), $newspost->id);
        } else if (($vote->upvote == 1 && $request->input('vote') === 'true') || ($vote->upvote == 0 && $request->input('vote') === 'false')) {
            Auth::user()->remove_post_vote($newspost->id);
        } else {
            Auth::user()->update_post_vote($newspost->id, $request->input('vote'));
        }

        $newspost->refresh();
        return response()->json(array('votes' => $newspost->score));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\NewsPost $newsPost
     * @return \Illuminate\Http\Response
     */
    public function edit(NewsPost $newspost)
    {
        if (!Auth::check()) return redirect('login');
        $this->authorize('owner', $newspost);

        $tags = Tag::orderBy('name', 'asc')->get();
        return view('pages.edit_post', ['post' => $newspost, 'tags' => $tags]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\NewsPost $newsPost
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, NewsPost $newspost)
    {
        if (!Auth::check()) return response()->json('Forbidden access', 403);
        $this->authorize('owner', $newspost);

        $validator = $this->post_validator($request->all());
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Initiate post update transaction
        DB::beginTransaction();

        // Update info
        try {
            $newspost->title = $request->input('title');
            if ($request->has('body')) {
                $newspost->body = $request->input('body');
            }

            $newspost->save();
        } catch (ValidationException $e) {
            DB::rollBack();
            return back()->withErrors(['dberror' => $e->getMessage()])->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        // Insert all new Post tags
        foreach ($request->input('tags') as $name) {
            if (!$newspost->tags->containsStrict('name', $name)) {
                try {
                    DB::table('tag')->insertOrIgnore([['name' => $name]]);

                    $id_tag = tag::firstWhere('name', $name)->id;
                    DB::table('post_tag')->insert(['id_post' => $newspost->id, 'id_tag' => $id_tag]);
                } catch (ValidationException $e) {
                    DB::rollBack();
                    return back()->withErrors(['dberror' => $e->getMessage()])->withInput();
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }
        }

        // Delete removed tags
        foreach ($newspost->tags as $tag) {
            if (!in_array($tag->name, $request->input('tags'))) {
                try {
                    DB::table('post_tag')->where(['id_post' => $newspost->id, 'id_tag' => $tag->id])->delete();
                } catch (ValidationException $e) {
                    DB::rollBack();
                    return back()->withErrors(['dberror' => $e->getMessage()])->withInput();
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }
        }


        if ($request->hasFile('images')) {
            // Delete old images
            foreach ($newspost->images as $image) {
                try {
                    Storage::delete('public/' . $newspost->id);
                    DB::table('post_image')->where(['id_post' => $newspost->id, 'file' => $image->file])->delete();
                } catch (ValidationException $e) {
                    DB::rollBack();
                    return back()->withErrors(['dberror' => $e->getMessage()])->withInput();
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }


            // Insert new Post Images
            $images = $request->file('images');

            $path = 'public/'. $newspost->id;
            Storage::makeDirectory($path);

            foreach ($images as $image) {
                try {
                    DB::table('post_image')->insert(['id_post' => $newspost->id, 'file' => $image->hashName()]);
                    $image->store($path);
                } catch (ValidationException $e) {
                    DB::rollBack();
                    Storage::deleteDirectory($path);
                    return back()->withErrors(['dberror' => $e->getMessage()])->withInput();
                } catch (\Exception $e) {
                    DB::rollBack();
                    Storage::deleteDirectory($path);
                    throw $e;
                }
            }
        }

        DB::commit();

        return redirect(route('post', ['newspost' => $newspost->id]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\NewsPost $newsPost
     * @return \Illuminate\Http\Response
     */
    public function destroy(NewsPost $newspost)
    {
        if (!Auth::check()) return response()->json('Forbidden access', 403);
        $this->authorize('delete', $newspost);

        Storage::deleteDirectory('public/' . $newspost->id);

        $newspost->delete();
    }


    public function search(Request $request)
    {
        if ($request->has('query') && $request->has('page')) {
            $posts = NewsPost::search_posts($request->input('query'), $request->input('page'));

            if (count($posts) > 0) {
                $html = [];
                foreach ($posts as $post) {
                    array_push($html, view('partials.postcard', ['post' => $post])->render());
                }
                return response()->json($html);
            }
        }

        return response()->json([view('partials.nocontent')->render()]);
    }



    public function report(Request $request, NewsPost $newspost)
    {
        if (!Auth::check()) return response()->json(array('auth' => 'Forbidden Access'), 403);

        Auth::user()->add_post_report($newspost->id, $request->input('report'));
    }

    public function dismiss(NewsPost $newspost)
    {
        if (!Auth::check() || !Auth::user()->admin) return response()->json(array('auth' => 'Forbidden Access'), 403);

        $newspost->dismiss_post_report();
    }
}

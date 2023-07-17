<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\NewsPost;
use App\Notifications\CommentNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function comment(Request $request, NewsPost $newspost)
    {
        if (!Auth::check()) return response()->json('Forbidden access', 403);

        $comment = Comment::create([
            'body' => $request->input('comment'),
            'date_time' => now(),
            'id_owner' => Auth::user()->id,
            'id_post' => $newspost->id
        ]);
        $comment->save();
        $comment->refresh();
        $html = view('partials.comment', ['comment' => $comment, 'offset' => 0])->render();
        $newspost->owner->notify(new CommentNotification($comment));
        return response()->json($html);
    }

    public function vote(Request $request, Comment $comment)
    {
        if (!Auth::check()) return response()->json(array('auth' => 'Forbidden Access'), 403);

        $vote = Auth::user()->hasVotedComment($comment->id);
        if ($vote === null) {
            Auth::user()->add_comment_vote($request->input('vote'), $comment->id);
        } else if (($vote->upvote == 1 && $request->input('vote') === 'true') || ($vote->upvote == 0 && $request->input('vote') === 'false')) {
            Auth::user()->remove_comment_vote($comment->id);
        } else {
            Auth::user()->update_comment_vote($comment->id, $request->input('vote'));
        }

        $comment->refresh();
        return response()->json(array('votes' => $comment->score));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Comment $comment)
    {
        if (!Auth::check()) return response()->json('Forbidden access', 403);
        $this->authorize('owner', $comment);
        $comment->body = $request->input('body');
        $comment->save();
        $post = $comment->post;

        $html = [];

        foreach ($post->parentComments() as $parent) {
            array_push($html, view('partials.comment', ['comment' => $parent, 'offset' => 0])->render());
        }

        return response()->json(array('html' => $html, 'body' => $comment->body));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Comment $comment)
    {
        if (!Auth::check()) return response()->json('Forbidden access', 403);
        $this->authorize('delete', $comment);

        $comment->delete();
    }

    public function report(Request $request, Comment $comment)
    {
        if (!Auth::check()) return response()->json(array('auth' => 'Forbidden Access'), 403);

        return CommentReport::create([
            'id_reporter' => Auth::user()->id,
            'id_comment' => $comment->id,
            'body' => $request->input('report')
        ]);
    }

    public function dismiss(Comment $comment)
    {
        if (!Auth::check() || !Auth::user()->admin) return response()->json(array('auth' => 'Forbidden Access'), 403);
        $comment->dismiss_report();
    }
}

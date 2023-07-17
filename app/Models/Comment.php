<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Notifications\CommentNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Comment extends Model
{
    use HasFactory;

    // Table
    protected $table = 'comment';

    // Don't add create and update timestamps in database.
    public $timestamps  = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'body', 'date_time', 'id_owner', 'id_post'
    ];

    /**
     * Get the member that owns the Comment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner()
    {
        return $this->belongsTo(Member::class, 'id_owner');
    }

    /**
     * Get the news post that owns the Comment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function post()
    {
        return $this->belongsTo(NewsPost::class, 'id_post');
    }

    public function scores()
    {
        return $this->belongsToMany(Member::class, 'score', 'id_comment', 'id_voter')
        ->where('vote_type', '=', 'comment')->withPivot('upvote');
    }


    public function get_time() {
        $time = Carbon::createFromFormat('Y-m-d H:i:s', $this->date_time);
        return $time->diffForHumans();
    }

        /**
     * Get all of the comments for the NewsPost
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reports()
    {
        return $this->hasMany(CommentReport::class, 'id_comment');
    }

    public function dismiss_report()
    {
        DB::table('comment_report')->where('id_comment', '=', $this->id)->delete();
    }


    public function add_vote($vote) {
        DB::table('vote')->insert([
            'id_comment' => $this->id,
            'id_voter' => Auth::user()->id,
            'upvote' => $vote,
            'vote_type' => 'comment'
        ]);
    }

    public function delete_vote() {
        DB::table('vote')
        ->where('id_voter', '=', Auth::user()->id)
        ->where('id_comment', '=', $this->id)
        ->where('vote_type', '=', 'comment')
        ->delete();
    }

}

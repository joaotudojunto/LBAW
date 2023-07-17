<?php

namespace App\Models;

use App\Traits\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Member extends Authenticatable{
    use HasFactory;
    use Notifiable;

    // Table
    protected $table = 'member';

    // Don't add create and update timestamps in database.
    public $timestamps  = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'contact', 'password', 'username', 'admin', 'isBanned'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password'
    ];

    /**
     * Get all of the news posts for the Member
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(NewsPost::class, 'id_owner');
    }

    /**
     * 
     */
    public function posts_scores()
    {
        return $this->belongsToMany(NewsPost::class, 'post_score', 'id_post', 'id_voter')->withPivot('upvote');
    }


    /**
     * Get all of the comments for the Member
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'id_owner');
    }

    /**
     * 
     */
    public function comments_scores()
    {
        return $this->belongsToMany(Comment::class, 'comment_score', 'id_comment', 'id_voter')->withPivot('upvote');
    }



    /**
     * The tags that the Member follows
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'tag_follow', 'id_member', 'id_tag')->orderBy('name');
    }

    /**
     * The members that follow the Member
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function followers()
    {
        return $this->belongsToMany(Member::class, 'member_follow', 'id_followed', 'id_follower')->orderBy('username');
    }

    /**
     * The members that the Member follows
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function following()
    {
        return $this->belongsToMany(Member::class, 'member_follow', 'id_follower', 'id_followed')->orderBy('username');
    }

    public function isMe(int $id)
    {
        return $id === $this->id;
    }

    public function hasVotedPost($id_post) {
        return DB::table('vote')->select('upvote')
        ->where('id_voter','=', $this->id)
        ->where('id_post', '=', $id_post)
        ->first();
    }

    public function hasVotedComment($id_comment){
        return DB::table('vote')->select('upvote')
        ->where('id_voter','=', $this->id)
        ->where('id_comment', '=', $id_comment)
        ->first();
    }

    public function isFollowing($id_member) {
        return DB::table('member_follow')->select('id_follower')
        ->where('id_follower','=',$this->id)
        ->where('id_followed','=',$id_member)
        ->first();
    }

    public function isFollowed($id_member) {
        return DB::table('member_follow')->select('id_follower')
        ->where('id_follower','=',$id_member)
        ->where('id_followed','=',$this->id)
        ->first();
    }

    public function reports()
    {
        return $this->hasMany(MemberReport::class, 'id_reported');
    }

    public function follow_member($id_member)
    {
        DB::table('member_follow')->insert([
            'id_followed' => $id_member,
            'id_follower' => $this->id,
        ]);
    }

    public function unfollow_member($id_member)
    {
        DB::table('member_follow')
            ->where('id_followed', '=', $id_member)
            ->where('id_follower', '=', $this->id)
            ->delete();
    }

    public function follow_tag($id_tag)
    {
        DB::table('tag_follow')->insert([
            'id_tag' => $id_tag,
            'id_member' => $this->id,
        ]);
    }

    public function unfollow_tag($id_tag)
    {
        DB::table('tag_follow')
        ->where('id_tag', '=', $id_tag)
        ->where('id_member', '=', $this->id)
        ->delete();
    }

    public function report_tag($id_tag, $report_body)
    {
        DB::table('tag_report')->insert([
            'id_reporter' => $this->id,
            'id_tag' => $id_tag,
            'body' => $report_body
        ]);
    }

    public function add_post_vote($vote, $id_post)
    {
        DB::table('vote')->insert([
            'id_post' => $id_post,
            'id_voter' => $this->id,
            'upvote' => $vote,
            'vote_type' => 'news_post'
        ]);
    }

    public function remove_post_vote($id_post)
    {
        DB::table('vote')
        ->where('id_voter', '=', $this->id)
        ->where('id_post', '=', $id_post)
        ->where('vote_type', '=', 'news_post')
        ->delete();
    }

    public function update_post_vote($id_post, $vote)
    {
        DB::table('vote')
            ->where('id_voter', '=', Auth::user()->id)
            ->where('id_post', '=', $id_post)
            ->where('vote_type', '=', 'news_post')
            ->update(['upvote' => $vote]);
    }

    public function add_comment_vote($vote, $id_comment)
    {
        DB::table('vote')->insert([
            'id_comment' => $id_comment,
            'id_voter' => $this->id,
            'upvote' => $vote,
            'vote_type' => 'comment'
        ]);
    }

    public function remove_comment_vote($id_comment)
    {
        DB::table('vote')
        ->where('id_voter', '=', $this->id)
        ->where('id_comment', '=', $id_comment)
        ->where('vote_type', '=', 'comment')
        ->delete();
    }

    public function update_comment_vote($id_comment, $vote)
    {
        DB::table('vote')
            ->where('id_voter', '=', Auth::user()->id)
            ->where('id_comment', '=', $id_comment)
            ->where('vote_type', '=', 'comment')
            ->update(['upvote' => $vote]);
    }


    public function add_post_report($id_post, $report_body)
    {
        DB::table('post_report')->insert([
            'id_reporter' => $this->id,
            'id_post' => $id_post,
            'body' => $report_body
        ]);
    }

    public function add_member_report($id_member, $report_body)
    {
        DB::table('member_report')->insert([
            'id_reporter' => $this->id,
            'id_reported' => $id_member,
            'body' => $report_body
        ]);
    }

    public function dismiss_member_report()
    {
        MemberReport::where('id_reported', '=', $this->id)->delete();
    }

    public static function search_members($query, $page)
    {
        return Member::whereRaw('ts_vectors @@ plainto_tsquery(\'english\', ?)',  [$query])
        ->orderByRaw('ts_rank(ts_vectors, plainto_tsquery(\'english\', ?)) DESC', [$query])
        ->orderBy('username', 'desc')
        ->forPage($page)
        ->get();
    }
}

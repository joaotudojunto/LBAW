<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NewsPost extends Model
{
    use HasFactory;

    // Table
    protected $table = 'news_post';


    // Don't add create and update timestamps in database.
    public $timestamps  = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title', 'body', 'date_time', 'id_owner',
    ];

    /**
     * Get the member that owns the NewsPost
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner()
    {
        return $this->belongsTo(Member::class, 'id_owner');
    }

    /**
     * The tags that belong to the NewsPost
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tag', 'id_post', 'id_tag');
    }

    /**
     * Get all of the images for the NewsPost
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images()
    {
        return $this->hasMany(PostImage::class, 'id_post');
    }

    /**
     * Get all of the comments for the NewsPost
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'id_post');
    }

    /**
     * Get all of the comments for the NewsPost
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reports()
    {
        return $this->hasMany(PostReport::class, 'id_post');
    }

    /**
     * The votes that belong to the NewsPost
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function scores()
    {
        return $this->belongsToMany(Member::class, 'post_score', 'id_post', 'id_voter')
        ->where('vote_type', '=', 'news_post')->withPivot('upvote');
    }

    /**
     * The correct date
     */
    public function getDateFormat() {
        return 'Y-m-d H:i:s';
    }

    /**
     * Get all of the comments for the NewsPost
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function parentComments()
    {
        $parentComments = [];
        $aux= DB::select(DB::raw("SELECT comment.id
        FROM comment
        WHERE id_post = ".$this->id."
        ORDER BY date_time DESC"));
        foreach($aux as $auxIds ){
            array_push($parentComments,Comment::find($auxIds->id));
        }
        return $parentComments;
    }


    public function get_time() {
        $time = Carbon::createFromFormat($this->getDateFormat(), $this->date_time);
        return $time->diffForHumans();
    }


    public static function feed($num_rows)
    {
        return DB::select(DB::raw("SELECT news_post.id as id
        FROM news_post
        INNER JOIN member ON id_owner = member.id
        WHERE news_post.id IN
        (
            SELECT DISTINCT news_post.id FROM news_post
            INNER JOIN post_tag ON news_post.id = post_tag.id_post
            INNER JOIN tag ON post_tag.id_tag = tag.id
            INNER JOIN member_follow ON member_follow.id_follower = ?
            WHERE tag.name IN
            (
                SELECT name FROM tag
                INNER JOIN tag_follow ON tag.id = tag_follow.id_tag
                WHERE tag_follow.id_member = ?
            )
            OR
            member_follow.id_followed = id_owner
        ) ORDER BY date_time DESC
        OFFSET ? ROWS
        FETCH NEXT 15 ROWS ONLY"), [Auth::user()->id, Auth::user()->id, $num_rows]);

    }

    public static function trending_posts($num_rows)
    {
        return DB::select(DB::raw("SELECT id
            FROM news_post
            WHERE date_time >= (now() - interval '14 days')
            ORDER BY news_post.title DESC
            OFFSET ? ROWS
            FETCH NEXT 15 ROWS ONLY"), [$num_rows]);
    }


    public function dismiss_post_report()
    {
        DB::table('post_report')->where('id_post', '=', $this->id)->delete();
        // PostReport::where('id_post', '=', $this->id)->delete();
        // $this->refresh();
    }

    public static function search_posts($query, $page)
    {
        return NewsPost::whereRaw('ts_vectors @@ plainto_tsquery(\'english\', ?)', [$query])
        ->orderByRaw('ts_rank(ts_vectors, plainto_tsquery(\'english\', ?)) DESC', [$query])
        ->orderBy('date_time', 'desc')
        ->forPage($page)
        ->get();
    }
}

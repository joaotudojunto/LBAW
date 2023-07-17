<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Tag extends Model{
    use HasFactory;

    // Table
    protected $table = 'tag';

    // Don't add create and update timestamps in database.
    public $timestamps  = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name'
    ];

    /**
     * The members that follow the tag
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function followers()
    {
        return $this->belongsToMany(Member::class, 'tag_follow', 'id_tag', 'id_member');
    }

    /**
     * The news posts that have the tag
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function posts()
    {
        return $this->belongsToMany(NewsPost::class, 'post_tag', 'id_tag', 'id_post');
    }

    public function isFollowed($id_member) {
        return DB::table('tag_follow')->select('id_tag')
        ->where('id_tag','=',$this->id)
        ->where('id_member','=',$id_member)
        ->first();
    }

    public static function search_tags($query, $page)
    {
        return Tag::whereRaw('ts_vectors @@ plainto_tsquery(\'english\', ?)', [$query])
        ->orderByRaw('ts_rank(ts_vectors, plainto_tsquery(\'english\', ?)) DESC', [$query])
        ->forPage($page)
        ->get();
    }

    public static function tag_trending_posts($id_tag, $num_rows)
    {
        return DB::select(DB::raw("SELECT news_post.id as id
            FROM news_post
            INNER JOIN post_tag ON news_post.id = id_post AND ? = id_tag
            WHERE date_time >= (now() - interval '14 days')
            ORDER BY news_post.id DESC
            OFFSET ? ROWS
            FETCH NEXT 15 ROWS ONLY"), [$id_tag, $num_rows]);
    }

    public static function popular_tags()
    {
        $num_followers_tags = DB::table('tag_follow')
            ->select('id_tag', DB::raw('COUNT(*) AS num_followers'))
            ->groupBy('id_tag');

        $popular_tags = DB::table('tag')->joinSub($num_followers_tags, 'num_followers_tags', function($join) {
                $join->on('tag.id', '=', 'num_followers_tags.id_tag');
            })->orderBy('num_followers', 'desc')->limit(5)->get();

        return $popular_tags;
    }
    
}

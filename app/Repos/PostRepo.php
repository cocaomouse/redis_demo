<?php

namespace App\Repos;

use App\Models\Post;
use Illuminate\Support\Facades\Redis;
use Cache;

class PostRepo
{
    protected $post;
    protected $trendingPostsKey = 'popular_posts';

    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    public function getById(int $id,array $columns = ['*'])
    {
        $cacheKey = 'post_' . $id;

        // 查询redis中是否存在$chacheKey对应的$value,存在return $value;
        // 不存在则调用匿名函数生成$value,以$cacheKey为key,存入redis中;
        return Cache::remember($cacheKey,1*60*60,function () use ($id,$columns) {
            return $this->post->select($columns)->find($id);
        });
    }

    public function getByManyId(array $ids,array $columns = ['*'], callable $callback = null)
    {
        $query = $this->post->select($columns)->whereIn('id',$ids);
        if ($query) {
            $query = $callback($query);
        }
        return $query->get();
    }

    public function addViews(Post $post)
    {
        $post->increment('views');
        if($post->save()) {
            // 将当前文章浏览数 +1，存储到对应 Sorted Set 的 score 字段
            Redis::zincrby('popular_posts',1,$post->id);
        }
        return $post->views; //此处的$post不是$post->save()之后的集合,而是addViews($post)中一开始导入的$post
    }

    // 热门文章排行榜
    public function trending($num = 10)
    {
        $cacheKey = $this->trendingPostsKey . '_' . $num;

        return Cache::remember($cacheKey,10*60,function () use ($num) {
            $postIds = Redis::zrevrange($this->trendingPostsKey,0,$num-1);
            if ($postIds) {
                $idsStr = implode(',',$postIds);
                return $this->getByManyId($postIds,['*'],function ($query) use ($idsStr) {
                    return $query->orderByRaw('field(`id`,'.$idsStr.')');
                });
            }
        });
    }
}

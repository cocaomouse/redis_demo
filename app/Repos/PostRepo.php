<?php

namespace App\Repos;

use App\Models\Post;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

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

        // 查询redis中是否存在$cacheKey对应的$value,存在return $value;
        // 不存在则调用匿名函数生成$value,以$cacheKey为key,存入redis中;
        return Cache::remember($cacheKey,1*1*10,function () use ($id,$columns) {
            return $this->post->select($columns)->find($id);
        });

        /*if (Redis::exists($cacheKey)) {
            return unserialize(Redis::get($cacheKey));
        }
        $post = $this->post->select($columns)->find($id);
        if (!$post) {
            return null;
        }
        Redis::setex($cacheKey, 1 * 60 * 60, serialize($post));  // 缓存 1 小时
        return $post;*/

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
       /* $post->increment('views');
        if($post->save()) {
            // 将当前文章浏览数 +1，存储到对应 Sorted Set 的 score 字段
            Redis::zincrby('popular_posts',1,$post->id);
        }
        return $post->views; //此处的$post->views是$post->increment('views')之后的views值*/

        // 推送消息数据到队列，通过异步进程处理数据库更新
        Redis::rpush('post-views-increment',$post->id);
        return ++$post->views;
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

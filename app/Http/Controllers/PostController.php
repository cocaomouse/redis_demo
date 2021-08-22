<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\Redis;

class PostController extends Controller
{
    public function show(Post $post)
    {
        $post->increment('views');
        if ($post->save()) {
            // 将当前文章浏览数 +1，存储到对应 Sorted Set 的 score 字段
            Redis::zincrby('popular_posts',1,$post->id);
        }
        return 'Show Post #' . $post->id;
    }

    public function popular()
    {
        // 获取浏览器最多的前十篇文章
        $postIds = Redis::zrevrange('popular_posts',0,9);
        if ($postIds) {
            $idsStr = implode(',',$postIds);
            // 查询结果排序必须和传入时的 ID 排序一致
            $posts = Post::whereIn('id',$postIds)
                ->select(['id','title','views'])
                ->orderByRaw('field(`id`,'.$idsStr.')')
                ->get();
        } else {
            $posts = null;
        }
        dd($posts->toArray());
    }
}

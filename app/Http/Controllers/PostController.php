<?php

namespace App\Http\Controllers;

use App\Events\PostViewed;
use App\Jobs\PostViewsIncrement;
use App\Repos\PostRepo;
use Illuminate\Support\Facades\Redis;

class PostController extends Controller
{
    protected $postRepo;

    public function __construct(PostRepo $postRepo)
    {
        $this->postRepo = $postRepo;
    }

    public function show($id)
    {
        $post = $this->postRepo->getById($id);
        // 分发队列任务
        //$this->dispatch(new PostViewsIncrement($post));
        //$views = $this->postRepo->addViews($post);

        // 触发文章浏览事件
        event(new PostViewed($post));

        return "Show Post #{$post->id},Views: {$post->views}";
    }

    public function popular()
    {
        $posts  = $this->postRepo->trending(10);
        if ($posts) {
            dump($posts->toArray());
        }
    }
}

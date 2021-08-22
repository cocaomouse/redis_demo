<?php

namespace App\Http\Controllers;

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
        $views = $this->postRepo->addViews($post);
        return "Show Post #{$post->id},Views: {$views}";
    }

    public function popular()
    {
        $posts  = $this->postRepo->trending(10);
        if ($posts) {
            dump($posts->toArray());
        }
    }
}

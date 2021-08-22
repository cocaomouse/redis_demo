<?php

namespace App\Repos;

use App\Models\Post;

class PostRepo
{
    protected Post $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }
}

<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Post;
use Illuminate\Support\Facades\Redis;

class PostViewsIncrement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Post $post;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->post->increment('views')) {
            Redis::zincrby('popular_posts',1,$this->post->id);
        }

        /*Redis::funnel('post.views.increment')
            ->limit(60)
            ->then(function () {
                // 队列任务正常处理逻辑
                if ($this->post->increment('views')) {
                    Redis::zincrby('popular_posts', 1, $this->post->id);
                }
            }, function () {
               // 超出处理频率上限,延迟60s再执行
                $this->release(60);
            });*/
    }
}

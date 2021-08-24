<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\PostViewed;
use Illuminate\Support\Facades\Redis;

class IncreasePostViews implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(PostViewed $event)
    {
        if ($event->post->increment('views')) {
            Redis::zincrby('popular_posts',1,$event->post->id);
        }
    }
}

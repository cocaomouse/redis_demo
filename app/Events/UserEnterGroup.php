<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;


class UserEnterGroup
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $groupId;
    public $broadcastQueue = 'broadcast';

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, $groupId)
    {
        $this->user = $user;
        $this->groupId = $groupId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel('wechat.group.' . $this->groupId);
    }
}

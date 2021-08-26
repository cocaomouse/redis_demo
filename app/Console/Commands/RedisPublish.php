<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use App\Events\UserSignedUp;
use App\Events\UserSendMessage;
use App\Events\UserEnterGroup;
use App\Models\User;

class RedisPublish extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Redis Publish Message';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $user = User::find(1);
        //event(new UserSignedUp($user));
        $message = '你好, Tester!';
        $groupId = 1;
        //event(new UserSendMessage($user, $message, $groupId));
        event(new UserEnterGroup($user, $groupId));
    }
}

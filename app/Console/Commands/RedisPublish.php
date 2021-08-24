<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

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
        $data = [
            'event' => 'UserSignedUp',
            'data' => [
                'username' => 'Tester',
            ]
        ];
        Redis::publish('test-channel',json_encode($data));
    }
}

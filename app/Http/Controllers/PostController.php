<?php

namespace App\Http\Controllers;

use App\Events\PostViewed;
use App\Jobs\PostViewsIncrement;
use App\Repos\PostRepo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Redis;
use App\Models\User;
use App\Jobs\ImageUploadProcessor;
use Illuminate\Http\Request;

class PostController extends Controller
{
    protected $postRepo;

    public function __construct(PostRepo $postRepo)
    {
        $this->postRepo = $postRepo;
        // 需要登录论证后才能发布位置
        $this->middleware['auth']->only(['create','store']);
    }

    public function show($id)
    {
        //$post = $this->postRepo->getById($id);
        // 分发队列任务
        //$this->dispatch(new PostViewsIncrement($post));
        //$views = $this->postRepo->addViews($post);

        // 触发文章浏览事件
        //event(new PostViewed($post));

        //return "Show Post #{$post->id},Views: {$post->views}";

        // 定义一个单位时间内限定请求上限等限流器,每秒最多支持100个请求
        return Redis::throttle("posts.${id}.show.concurrency")
            ->allow(100)->every(1)
            ->then(function () use($id) {
                // 正常访问
                $post = $this->postRepo->getById($id);
                event(new PostViewed($post));
                return view('posts.show',['post' => $post]);
            },function () {
                // 触发并发访问上限
                abort(429,'Too Many Requests');
            });
    }

    // 文章发布页
    public function create(Request $request)
    {
        return view('posts.create');
    }

    // 文章发布处理
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'content' => 'required|string|min:10',
            'image' => 'required|string|max:1024' // 尺寸不能超过1MB
        ]);

        $post = new Post($data);
        $post->user_id = $request->user()->id;

        try {
            if ($post->save()) {
                $image = $request->file('image');
                // 获取图片名称
                $name = $image->getClientOriginalName();
                // 获取图片二进制数据后通过Base64编码
                $content = $image->getContent();
                // 获取图片存储的临时路径
                $path = $image->store('temp');
                // 通过图片处理任务类将图片存储工作推送到 uploads 队列异步处理
                ImageUploadProcessor::dispatch($name,$path,$post)->onQueue('uploads');
                return redirect('posts/' . $post->id);
            }
            return back()->withInput()->with(['status' => '文章发布失败，请重试']);
        } catch (QueryException $e) {
            return back()->withInput()->with(['status' => '文章发布失败，请重试']);
        }
    }

    public function popular()
    {
        $posts  = $this->postRepo->trending(10);
        if ($posts) {
            dump($posts->toArray());
        }
    }
}

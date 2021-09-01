<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Image;
use App\Models\Post;
use Illuminate\Support\Facades\Storage;

class ImageUploadProcessor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // 文件名
    public $name;
    // 文件内容
    public $content;
    // 所属文章
    public $post;
    // 临时文件路径
    public $path;

    // 最大尝试次数,超过标记为执行失败
    public $tries = 10;
    // 最大异常数,超过标记为执行失败
    public $maxExceptions = 3;
    // 超时时间,3分钟,超过则标记为执行失败
    public $timeout = 180;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $name,string $path,Post $post)
    {
        $this->name = $name;
        $this->post = $post;
        $this->path = $path;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $destPath = 'images/' . $this->name;
        // 如果目标文件已存在或者临时文件不存在，则退出
        if (Storage::disk('public/')->exists($destPath) || !Storage::disk('local/')->exists($this->path)) {
            return ;
        }
        // 如果文件存储成功,则将其保存到数据库,否则5s后重试
        if (Storage::disk('public')->put($destPath,Storage::disk('local')->get($this->path))) {
            $image = new Image();
            $image->name = $this->name;
            $image->path = $destPath;
            $image->url = config('app.url') . '/storage/' . $destPath;
            $image->user_id = $this->post->user_id;
            if ($image->save()) {
                // 图片保存成功,则更新posts表到image_id字段
                $this->post->image_id = $image->id;
                $image->post->save($this->post);
                // 删除临时文件
                Storage::disk('public')->delete($this->path);
            } else {
                // 图片保存失败,则删除当前图片,并在5s后重试此任务
                Storage::disk('public')->delete($destPath);
                $this->release(5);
            }
        } else { // 如果有缩略图、裁剪等后续处理,可以在这里执行
            $this->release(5);
        }
    }
}

<?php

namespace App\Jobs;

use App\Http\Controllers\WechatController;
use App\Jobs\Job;
use App\Library\WechatHelper;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use DB;
use Log;


class DownloadImage extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $message;
    protected $reginfoId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($message, $reginfoId)
    {
        $this->message = $message;
        $this->reginfoId = $reginfoId;
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $message = $this->message;

            $currtime = date("Y-m-d H:i:s");

            $mediaId = $message->MediaId;

            $relativePath = "origin" . DIRECTORY_SEPARATOR . date("ymd")
                . DIRECTORY_SEPARATOR . $message->FromUserName . DIRECTORY_SEPARATOR;//保存数据库用

            $wechatHelper = new WechatHelper();

            $downloadResult = $wechatHelper->downloadImage($mediaId, $relativePath, date('ymdhim') . $this->reginfoId);
            Log::info("下载图片成功".$downloadResult);
            if ($downloadResult === false || !file_exists($relativePath . $downloadResult)) {
                Log::info($downloadResult);
                $wechatHelper->sendTextMsg($message->FromUserName, '提示网络不好，重新注册');
                return true;
            }

            //注册成功，更新注册信息
            DB::table('wechat_user_reginfo')->where('id', $this->reginfoId)
                ->update(['logo_local_path' => $relativePath . $downloadResult
                    , 'media_id' => $message->MediaId
                    , 'reg_status' => WechatController::REG_STATUS_FINISHED
                    , 'updated_at' => $currtime]);

            Log::info("注册信息保存成功");

            DB::table('wechat_user')
                ->where('openid', $message->FromUserName)
                ->update(['last_active_time' => $currtime]);

            $wechatHelper->sendTextMsg($message->FromUserName, '恭喜你，注册成功，立刻点击【获取图片】，每天点击，每天有新图[愉快][愉快]');

            return true;
        } catch (\Exception $e) {
            Log::info("下载图片失败");
            return true;
        }

    }
    /**
     * Handle a job failure.
     *
     * @return void
     */
    public function failed()
    {
        Log::info("注册信息保存成功");
        // Called when the job is failing...
    }
}

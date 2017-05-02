<?php

namespace App\Library;

use EasyWeChat\Foundation\Application;
use EasyWeChat\Message\Image;
use EasyWeChat\Message\Text;
use Log;
/**
 */
class WechatHelper
{
    private $wechat;

    public function  __construct()
    {
        $this->wechat = new Application(config('wechat'));
    }

    public function uploadImage($openid, $imageFile)
    {
        try {
            //上传图片到服务器
            $temporary = $this->wechat->material_temporary;
            $result = $temporary->uploadImage($imageFile);
            return $result->media_id;

        } catch (\Exception $e) {
            return false;
        }
        return false;

    }

    /**
     * 该方法备用
     * 客服消息，主动给用户发送文字消息
     * @param $message
     */
    public function sendTextMsg($openId, $text)
    {
        try {
            $wechat = $this->wechat;
            $message = new Text(['content' => $text]);
            return $wechat->staff->message($message)->to($openId)->send();//发送客服消息
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 该方法备用
     * 客服消息，主动给用户发送图片消息消息
     * @param $message
     */
    public function sendImageMsg($openId, $mediaId)
    {
        try {
            $wechat = $this->wechat;
            $message = new Image(['media_id' => $mediaId]);
            return $wechat->staff->message($message)->to($openId)->send();//发送客服消息
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 下载图片
     * @param $media_id
     * @param $relativePath
     * @param $filename
     * @return bool
     */
    public function downloadImage($media_id, $relativePath, $filename)
    {
        try {
            $realPath = public_path() . DIRECTORY_SEPARATOR . $relativePath;//保存文件用绝对地址
            if (!file_exists($realPath))
                @mkdir($realPath, 0755, true);
            //下载图片到本地
            $wechat = $this->wechat;
            $material = $wechat->material_temporary;
            $fileResult = $material->download($media_id, $realPath, $filename);

            return $fileResult;
        } catch (\Exception $e) {
            Log::info("下载图片异常了了了"."MediaId:".$media_id."realPath:".$realPath." FILENAME:".$filename."fileResult");
            return false;
        }
        return false;
    }



    /**
     * 直接回复客户图片消息，被动回复
     * @param $descPicFileName
     * @return Image|string
     */
    public function _replyImageMsg($mediaId)
    {
        try {
            //上传图片到服务器
            return new Image(['media_id' => $mediaId]);
        } catch (\Exception $e) {
            return '获取图片失败';
        }
    }


}
<?php
/**
 * Created by PhpStorm.
 * User: xianghongwang
 * Date: 2017/4/23
 * Time: 22:20
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Log;
use EasyWeChat\Foundation\Application;
use EasyWeChat\Message\Image;
use EasyWeChat\Message\Text;
use App\Library\Graph;


class WechatController extends Controller
{
    const REG_STATUS_FINISHED = 1;

    const REG_STATUS_REGING = 0;

    const SUBCRIBE_YES = 1;

    const SUBCRIBE_NO = 0;

    /**
     * 处理微信的请求消息
     *
     * @return string
     */
    public function serve(Request $request)
    {

        $wechat = app('wechat');
        $wechat->server->setMessageHandler(function ($message) {

            switch ($message->MsgType) {
                case 'event':
                    $result = $this->_receiveEventMsg($message);
                    break;
                case 'text':
                    $result = $this->_receiveTextMsg($message);
                    break;
                case 'image':
                    $result = $this->_receivePicMsg($message);
                    break;
//                case 'voice':
//                    return '收到语音消息';
//                    break;
//                case 'video':
//                    return '收到视频消息';
//                    break;
//                case 'location':
//                    return '收到坐标消息';
//                    break;
//                case 'link':
//                    return '收到链接消息';
//                    break;
                // ... 其它消息
                default:
                    return '我们每天为你做一张图，看起来很简单，但我们不敢怠慢，我们精心设计每一张图，认真挑选每天正能量的词语，希望与大家一起传递问候，传递正能量。

回复【注册】上传你的专属logo，获得我们为你制作的每日一图服务。

完成注册后点击【获取图片】即可立即获得你的专属图片。';
                    break;
            }

            if ($result !== false) {
                return $result;
            }
        });

        Log::info('return response.');

        return $wechat->server->serve();
    }

    /**
     * 收到文字消息处理
     * @param $message
     * @return string
     */

    private function _receiveTextMsg($message)
    {
        //获取最近一条注册信息
        $wechat_user_reginfo = DB::table('wechat_user_reginfo')->where('openid', $message->FromUserName)->orderBy('id', 'desc')->first();
        //局部变量
        $currtime = date("Y-m-d H:i:s", time());

        $wechat_user = DB::table('wechat_user')->where('openid', $message->FromUserName)->first();

        if (empty($wechat_user)) {//判断用户是否首次产生行为
            DB::table('wechat_user')->insert([
                'openid' => $message->FromUserName,
                'last_active_time' => $currtime,
                'created_at' => $currtime,
                'subscribe_yes' => self::SUBCRIBE_NO //0 ,非活动期间加入的用户，1，活动期间新关注用户
            ]);
        }

        if ($message->Content == '注册') {

            //判断最新的注册流程是否走完
            if (!$wechat_user_reginfo || $wechat_user_reginfo->reg_status == self::REG_STATUS_FINISHED) {//1，之前一次注册完成，需用重新注册
                DB::table('wechat_user_reginfo')->insert([
                    'openid' => $message->FromUserName,
                    'created_at' => $currtime,
                ]);
            }

            DB::table('wechat_user')
                ->where('openid', $message->FromUserName)
                ->update(['last_active_time' => $currtime]);

            return '请上传你的logo图，支持格式为png和jpg';

        } else {

//            if (empty($wechat_user_reginfo)) {
//                return '你尚未注册，还不能获得你的专属图片，请在对话框输入“注册”进入注册流程，完成注册后即可获得你的专属好图';
//            }
//
//
//            //判断是否输入产品或品牌名称
//            $wechat_user = DB::table('wechat_user')->where('openid', $message->FromUserName)->first();
//
//            if ($wechat_user && $wechat_user->last_msg_type == 'image') {
//
//                //获取最后一条注册信息，更新文字记录
//                DB::table('wechat_user_reginfo')->where('id', $wechat_user_reginfo->id)
//                    ->update(['show_text' => $message->Content, 'updated_at' => $currtime]);
//
//                DB::table('wechat_user')
//                    ->where('openid', $message->FromUserName)
//                    ->update(['last_msg_type' => 'text', 'last_msg_content' => $message->Content, 'last_msg_time' => $currtime]);


//                return '恭喜你，完成注册，立刻点击我要好图，即可获得属于你的专属好图';
//            } else {
//                return '输入"注册"修改logo和产品信息';
//            }
            return '谢谢你的参与，你可以输入[注册]来修改你的logo';
        }
    }

    /**
     * 收到图片消息处理
     * @param $message
     * @return string
     */
    private function _receivePicMsg($message)
    {

        try {

            //获取最后一条注册信息
            $wechat_user_reginfo = DB::table('wechat_user_reginfo')->where('openid', $message->FromUserName)->orderBy('id', 'desc')->first();

            if ($wechat_user_reginfo && $wechat_user_reginfo->reg_status == self::REG_STATUS_REGING) {//进入注册流程

                $currtime = date("Y-m-d H:i:s");

                $mediaId = $message->MediaId;

                $relativePath = "origin" . DIRECTORY_SEPARATOR . date("ymd")
                    . DIRECTORY_SEPARATOR . $message->FromUserName . DIRECTORY_SEPARATOR;//保存数据库用
                Log::info("生成下载目录成功");
                $downloadResult = $this->_downloadImage($mediaId, $relativePath, date('ymdhim') . $wechat_user_reginfo->id);
                Log::info("下载图片成功");
                if($downloadResult === false) {
                    $this->_sendTextMsg($message->FromUserName,'上传图片失败，请过一分钟之后再试！');
                    return'';
                }

                //注册成功，更新注册信息
                DB::table('wechat_user_reginfo')->where('id', $wechat_user_reginfo->id)
                    ->update(['logo_local_path' => $relativePath.$downloadResult
                        , 'media_id' => $message->MediaId
                        , 'reg_status' => self::REG_STATUS_FINISHED
                        , 'updated_at' => $currtime]);
                Log::info("注册信息保存成功");

                DB::table('wechat_user')
                    ->where('openid', $message->FromUserName)
                    ->update(['last_active_time' => $currtime]);

                Log::info("上传成功，接下来发送消息");

//                $this->_sendTextMsg($message->FromUserName,'注册成功，你可以点击菜单获取图片来获取你最新的作品！');
//                return '';
                return '注册成功，你可以点击菜单获取图片来获取你最新的作品！';

            } else {
                return '你没有进入注册流程，已注册过可直接点击菜单"获取图片"参与活动过！';
            }
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }

    private function _downloadImage($media_id, $relativePath, $filename)
    {
        try {
            $realPath = public_path() . DIRECTORY_SEPARATOR . $relativePath;//保存文件用绝对地址
            if (!file_exists($realPath))
                @mkdir($realPath, 0755, true);
            //下载图片到本地
            $wechat = new Application(config('wechat'));
            $material = $wechat->material_temporary;
            $fileResult = $material->download($media_id, $realPath, $filename);
            return $fileResult;
        } catch (\Exception $e) {
            Log::info(var_export($e,true));
            return false;
        }
        return false;
    }


    /**
     * 收到事件消息处理
     * @param $message
     * @return string
     */
    private
    function _receiveEventMsg($message)
    {

        if ($message->Event == 'subscribe') {

            $wechat_user = DB::table('wechat_user')->where('openid', $message->FromUserName)->first();
            if (empty($wechat_user)) {

                DB::table('wechat_user')->insert([
                    'openid' => $message->FromUserName,
                    'last_active_time' => date("YY-m-d H:i:s"),
                    'created_at' => date("YY-m-d H:i:s"),
                    'subscribe_yes' => self::SUBCRIBE_YES //0 ,非活动期间加入的用户，1，活动期间新关注用户
                ]);
            }

            return '感谢你关注有一套！

我们每天为你做一张图，看起来很简单，但我们不敢怠慢，我们精心设计每一张图，认真挑选每天正能量的词语，希望与大家一起传递问候，传递正能量。

回复【注册】上传你的专属logo，获得我们为你制作的每日一图服务。

完成注册后点击【获取图片】即可立即获得你的专属图片。
';

        } else if ($message->Event == 'CLICK') {//菜单事件

            if ($message->EventKey == 'V1001_GET_PIC') {
                //获取最近一条注册信息
                $wechat_user_reginfo = DB::table('wechat_user_reginfo')->where('openid', $message->FromUserName)->orderBy('id', 'desc')->first();
                //局部变量
                $currtime = date("Y-m-d H:i:s", time());

                //判断最新的注册流程是否走完
                if (!$wechat_user_reginfo || $wechat_user_reginfo->reg_status == self::REG_STATUS_REGING) {//1，之前一次注册未完成
                    return '你还没有注册过，可输入文字回复注册参与活动，谢谢！';
                } else {//注册用户
                    $wechat_compose_details = DB::table('wechat_compose_details')->where('openid', $message->FromUserName)->orderBy('id', 'desc')->first();

                    if (empty($wechat_compose_details)
                        || date("ymd") != date("ymd", strtotime($wechat_compose_details->created_at))
                        || $wechat_compose_details->reginfo_id != $wechat_user_reginfo->id
                    ) {//是否有最新的模版需要合成，有需要合成的图片先合成图片，再发送图片消息

                        $logoRelativeUrl = $wechat_user_reginfo->logo_local_path;

                        $tempDir = 'template' . DIRECTORY_SEPARATOR;//模版文件目录

                        $dateUrl = $tempDir . date('Ymd') . DIRECTORY_SEPARATOR . 'date.png';

                        $bgColorUrl = $tempDir . "bg_white.png";

                        $tempFile = $tempDir . date('Ymd')  . DIRECTORY_SEPARATOR . "bg.jpeg";

                        $descDir = 'works' . DIRECTORY_SEPARATOR . date("Ymd") . DIRECTORY_SEPARATOR . $message->FromUserName . DIRECTORY_SEPARATOR;

                        if (!file_exists($descDir))
                            @mkdir($descDir, 0755, true);

                        $descPicFileName = $descDir . time() . '.jpeg';
                        $result = Graph::composeImages($tempFile, $logoRelativeUrl, $dateUrl, $bgColorUrl, $descPicFileName);

                        if ($result === true) {//合成图片成功

                            $updateImage = $this->_uploadImage($message->FromUserName, $descPicFileName);
                            $mediaId = $updateImage !== false ? $updateImage : '';
                            DB::table('wechat_compose_details')->insert([
                                'openid' => $message->FromUserName,
                                'reginfo_id' => $wechat_user_reginfo->id,
                                'created_at' => $currtime,
                                'template_pic' => $tempFile,//0 ,非活动期间加入的用户，1，活动期间新关注用户
                                'final_pic' => $descPicFileName,
                                'media_id' => $mediaId,
                                'pull_time' => '1'
                            ]);

                            DB::table('wechat_user')
                                ->where('openid', $message->FromUserName)
                                ->increment('pic_num', 1, ['last_active_time' => $currtime]);//更新用户表

                            if ($mediaId)
                                $this->_sendImageMsg($message->FromUserName, $mediaId);
//                            return '图片正在合成，马上就好，不用重复点击获取"图片按钮"！';
                        } else {
                            return '合成图片失败' . $result;
                        }
                    } else {
                        $mediaId ='';

                        if (empty($wechat_compose_details->media_id)) {
                            $updateImage = $this->_uploadImage($message->FromUserName, $wechat_compose_details->final_pic);
                            if ($updateImage !== false) {
                                $mediaId = $updateImage;
                                DB::table('wechat_compose_details')
                                    ->where('id', $wechat_compose_details->id)
                                    ->increment('pull_time', 1, ['media_id' => $mediaId]);
                            }

                        } else {
                            $mediaId = $wechat_compose_details->media_id;
                            DB::table('wechat_compose_details')
                                ->where('id', $wechat_compose_details->id)
                                ->increment('pull_time', 1);
                        }

                        if ($mediaId){
                            return $this->_replyImageMsg($mediaId);//已经合成的图片重复拉取，直接发送图片消息
                        }else{
                            return $this->_sendTextMsg($message->FromUserName,'网络不好，请重新拉取！');
                        }
                            //$this->_sendImageMsg($message->FromUserName, $mediaId);


                    }

                }
            }

        } else {
            return '你尚未注册，还不能获得你的专属图片，请在对话框输入“注册”进入注册流程，完成注册后即可获得你的专属好图';
        }
    }


    private function _uploadImage($openid, $imageFile)
    {
        try {
            //上传图片到服务器
            $wechat = new Application(config('wechat'));
            $temporary = $wechat->material_temporary;
            $result = $temporary->uploadImage($imageFile);
            return (isset($result->media_id) and !empty($result->media_id))?$result->media_id:false;

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
    private function _sendTextMsg($openId, $text)
    {
        try {
            $wechat = new Application(config('wechat'));
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
    private function _sendImageMsg($openId, $mediaId)
    {
        try {
            $wechat = new Application(config('wechat'));
            $message = new Image(['media_id' => $mediaId]);
            return $wechat->staff->message($message)->to($openId)->send();//发送客服消息
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 直接回复客户图片消息，被动回复
     * @param $descPicFileName
     * @return Image|string
     */
    private function _replyImageMsg($mediaId)
    {
        try {
            //上传图片到服务器
            return new Image(['media_id' => $mediaId]);
        } catch (\Exception $e) {
            return '获取图片失败';
        }
    }

    public function setMenu(Request $request)
    {
        $app = new Application(config('wechat'));
        $menu = $app->menu;
        $buttons = [
            [
                "type" => "click",
                "name" => "获取图片",
                "key" => "V1001_GET_PIC"
            ],
        ];
        $result = $menu->add($buttons);
        var_dump($result);
        return '';

    }
}


?>
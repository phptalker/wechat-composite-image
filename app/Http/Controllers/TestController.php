<?php
/**
 * Created by PhpStorm.
 * User: xianghongwang
 * Date: 2017/4/23
 * Time: 22:20
 */

namespace App\Http\Controllers;

use EasyWeChat\Message\Image;
use App\Library\Graph;
use Illuminate\Http\Request;
use DB;
use Log;
use EasyWeChat\Foundation\Application;
use App\Library\WechatHelper;


class TestController extends Controller
{

    public function image2(Request $request)
    {
        try {
            $mediaId = 'GgHjQcqrfJQQh4Vm4e2Ct7JF1EYoJ7Ag0qfHyvFaSMt9mghFQB';

            $relativePath = "origin" . DIRECTORY_SEPARATOR . date("ymd")
                . DIRECTORY_SEPARATOR . 'oz131vm78nu_DVLXJHkbPJI7H_nA' . DIRECTORY_SEPARATOR;//保存数据库用
//            $wechatHelper = new WechatHelper();

//            $downloadResult = $wechatHelper->downloadImage($mediaId, $relativePath, 17050112460578);

            $wechat = new Application(config('wechat'));
            $material = $wechat->material_temporary;
            $fileResult = $material->download($mediaId, $relativePath, 17050112460578);
            var_dump($fileResult);
            return 'sdfsd';
        } catch (\Exception $e) {
            Log::info("下载图片失败");
            var_dump($e);
            return '失败';
        }
//        $tempDir = 'template' . DIRECTORY_SEPARATOR;//模版文件目录
//
//        $dateUrl = $tempDir . date('Ymd') . DIRECTORY_SEPARATOR . 'date.png';
//
//        $bgColorUrl = $tempDir . "bg_white.png";
//
//        $tempFile = $tempDir . date('Ymd')  . DIRECTORY_SEPARATOR . "bg.png";
//
//        $logoUrl  ='origin/170427/oz131vm78nu_DVLXJHkbPJI7H_nA/17042702150434.jpg';
//
//        if (!file_exists($logoUrl) || !file_exists($tempFile) || !file_exists($dateUrl))
//            return '没有最新的模版文件或在准备中，可以联系官方或等待一段时间之后再试！';
//
//        return;
//        DB::table('wechat_compose_details')
//            ->where('id', 1)->increment('pull_time', 1,['media_id'=> 'dss'])
////            ->update(['media_id'=> `pull_time ` ])
//            ;
//        return '';

//        header("Content-type: image/png");
//
//        $im = @imagecreate(200, 200)or die("创建图像资源失败");
//
//        $bg = imagecolorallocate($im, 204, 204, 204);
//        $red = imagecolorallocate($im, 255, 0, 0);
//
//        imagearc($im, 100, 100, 150, 150, 0, 360, $red);
//
//        imagepng($im,"works/circle.png");
//
//        imagedestroy($im);
//        return;
        $tempDir = 'template' . DIRECTORY_SEPARATOR;//模版文件目录

        $logoUrl = 'WechatIMG45.jpeg';
        $dateUrl = $tempDir . '20170427' . DIRECTORY_SEPARATOR . 'date.png';

        $bgColorUrl = $tempDir . "bg_white.png";
        $tempFile = "WechatIMG75.jpeg";//$tempDir . '20170427' . DIRECTORY_SEPARATOR . "bg.png";

        $result = Graph::composeImages($tempFile, $logoUrl, $dateUrl, $bgColorUrl, 'test.jpeg');
        return $result === true ? 1 : $result;
    }


}

?>

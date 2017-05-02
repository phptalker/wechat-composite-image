<?php

namespace App\Library;

/**
 * Graph
 * 图像操作类
 *
 * @author  hong <hong1070@qq.com>
 */
class Graph
{
    /**
     * make water_mark of picture. This function requires GD 2.0.1 or later,
     * parameter list like $arr[, $arr1, $arr2 ...], multiple parameters maybe astricted by memory
     *
     * 此函数传入的是php不定参数
     * array   parameters array, item list:
     *                             img_url, string, original image url;
     *                             mark_url, string, water_mark image url, must be PNG;
     *                             copy_name, string, copy original image with water_mark[optional];
     *                             scale_limit, number, scale switch with making water_mark[optional];
     *                             square_limit, number, double side limit with making water_mark[optional];
     *                             min_limit, number, min size with making water_mark[optional];
     *                             ref_point, number, reference point, maybe 0(upper left), 1(upper right), 2(lower right), 3(lower left)
     *                            pos_x, number, water_mark's x-coordinate of destination point relative to ref_point[optional];
     *                             pos_y, number, water_mark's y-coordinate of destination point relative to ref_point[optional];
     *                             pad, number, pad swtich[optional];
     *                             bgcolor, string, padding bgcolor[optional];
     *
     * @return      array result array, when one parameter, item list:
     *                             error, error number;
     *                             message, error message;
     *                             url, url of image with water_mark;
     *
     *                             multiple parameter, item list:
     *                             array(
     *                                 error, error number;
     *                                 message, error message;
     *                                 url, url of image with water_mark;
     *                             )
     *                             [, array(...)...]
     */
    public static function makeWatermark()
    {
        // get number of parameters
        $para_num = func_num_args();

        if ($para_num) {
            // count availability parameters
            $avail_para = 0;
            // get all parameters
            $para_arr = func_get_args();

            // initialize parameters
            $rs = array();
            $mark_url = '';
            $scale_switch = 100;
            $square_side = 0;
            $size_limit = 0;
            $ref_point = null;
            $pos_x = null;
            $pos_y = null;
            $do_pad = false;

            foreach ($para_arr as $para) {
                if (!empty ($para) && is_array($para)) {
                    // if empty original image url
                    if (empty ($para ['img_url'])) {
                        $err = 2;
                        $msg = 'Empty original image url';

                        $rs [$avail_para] ['error'] = $err;
                        $rs [$avail_para] ['message'] = $msg;
                        $rs [$avail_para] ['url'] = '';

                        continue;
                    }

                    // if empty water_mark image url
                    $cur_mark = '';

                    if (!empty ($para ['mark_url'])) {
                        $cur_mark = $para ['mark_url'];
                    } else if (!strlen($mark_url)) {
                        $err = 3;
                        $msg = 'Empty water_mark image url';

                        $rs [$avail_para] ['error'] = $err;
                        $rs [$avail_para] ['message'] = $msg;
                        $rs [$avail_para] ['url'] = '';

                        continue;
                    }

                    // get image info of original image
                    $src_img = $para ['img_url'];
                    $src_img_info = @getimagesize($src_img);

                    if (!$src_img_info) {
                        $err = 4;
                        $msg = 'Can not get info of original image ' . $src_img;

                        $rs [$avail_para] ['error'] = $err;
                        $rs [$avail_para] ['message'] = $msg;
                        $rs [$avail_para] ['url'] = '';

                        continue;
                    }

                    $src_img_w = $src_img_info [0];
                    $src_img_h = $src_img_info [1];
                    $src_img_t = $src_img_info [2];

                    // get reference point
                    if (isset ($para ['ref_point'])) {
                        $ref_point = abs(( int )$para ['ref_point']);

                        if ($ref_point > 3) {
                            $ref_point = 3;
                        }
                    }

                    $ref_point = ($ref_point === null) ? 2 : $ref_point;

                    // get position of water_mark
                    if (isset ($para ['pos_x'])) {
                        $pos_x = ( int )$para ['pos_x'];
                    }

                    $pos_x = ($pos_x === null) ? $src_img_w : $pos_x;

                    if (isset ($para ['pos_y'])) {
                        $pos_y = ( int )$para ['pos_y'];
                    }

                    $pos_y = ($pos_y === null) ? $src_img_h : $pos_y;

                    // duplicate switch
                    if (!empty ($para ['copy_name'])) {
                        $duplicate = true;
                    } else {
                        $duplicate = false;
                    }

                    // if set square limit of original image
                    if (!empty ($para ['square_limit'])) {
                        $square_side = ( int )$para ['square_limit'];
                    }

                    // if set min limit of original image
                    if (!empty ($para ['min_limit'])) {
                        $size_limit = ( int )$para ['min_limit'];
                    }

                    if ($square_side || $size_limit) {
                        // original image is too small
                        if (($src_img_w <= $square_side && $src_img_h <= $square_side) || ($src_img_w <= $size_limit) || ($src_img_h <= $size_limit)) {
                            $err = 0;
                            $msg = 'ok';
                            $new_url = '';

                            // duplicate image
                            if ($duplicate) {
                                umask(0);

                                $cp = copy($para ['img_url'], $para ['copy_name']);
                                @chmod($para ['copy_name'], 0777);

                                if (!$cp) {
                                    $err = 5;
                                    $msg = 'Duplicate original image failed';
                                } else {
                                    $new_url = $para ['copy_name'];
                                }
                            }

                            $rs [$avail_para] ['error'] = $err;
                            $rs [$avail_para] ['message'] = $msg;
                            $rs [$avail_para] ['url'] = $new_url;

                            continue;
                        }
                    }

                    // get image info and resource of water_mark image
                    if ($cur_mark != $mark_url) {
                        // get water_mark image info
                        $mrk_img_info = @getimagesize($cur_mark);

                        if (!$mrk_img_info) {
                            $err = 6;
                            $msg = 'Can not get info of water_mark image ' . $cur_mark;

                            $rs [$avail_para] ['error'] = $err;
                            $rs [$avail_para] ['message'] = $msg;
                            $rs [$avail_para] ['url'] = '';

                            continue;
                        }

                        $mrk_img_w = $mrk_img_info [0];
                        $mrk_img_h = $mrk_img_info [1];
                        $mrk_img_t = $mrk_img_info [2];

                        // if water_mark image is not PNG
                        if ($mrk_img_t != IMAGETYPE_PNG) {
                            $err = 7;
                            $msg = 'Type of water_mark image maybe PNG';

                            $rs [$avail_para] ['error'] = $err;
                            $rs [$avail_para] ['message'] = $msg;
                            $rs [$avail_para] ['url'] = '';

                            continue;
                        }

                        // get water_mark image resource
                        $mrk_im = @imagecreatefrompng($cur_mark);

                        if ($mrk_im) {
                            $mark_url = $cur_mark;
                        } else {
                            $err = 8;
                            $msg = 'Can not create resource from water_mark image';

                            $rs [$avail_para] ['error'] = $err;
                            $rs [$avail_para] ['message'] = $msg;
                            $rs [$avail_para] ['url'] = '';

                            continue;
                        }
                    }

                    $mark_im_res = $mrk_im;
                    $mark_x_offset = $mrk_img_w;
                    $mark_y_offset = $mrk_img_h;

                    // get scale switch with making water_mark
                    $scale_switch = !empty ($para ['scale_limit']) ? abs(( int )$para ['scale_limit']) : $scale_switch;

                    // get reference size of water_mark
                    $ref_size = ($src_img_w > $src_img_h) ? $src_img_h : $src_img_w;

                    // watermark scale
                    if ($ref_size < $scale_switch) {
                        // resize water_mark
                        $rate = $ref_size / $scale_switch;

                        $mrk_dst_w = ceil($mrk_img_w * $rate);
                        $mrk_dst_h = ceil($mrk_img_h * $rate);

                        $tmp_mark_im = @imagecreatetruecolor($mrk_dst_w, $mrk_dst_h);

                        @imagealphablending($tmp_mark_im, false);

                        @imagesavealpha($tmp_mark_im, true);

                        @imagecopyresampled($tmp_mark_im, $mrk_im, 0, 0, 0, 0, $mrk_dst_w, $mrk_dst_h, $mrk_img_w, $mrk_img_h);

                        if (!$tmp_mark_im) {
                            $err = 9;
                            $msg = 'Can not create resized image resource from water_mark image';

                            $rs [$avail_para] ['error'] = $err;
                            $rs [$avail_para] ['message'] = $msg;
                            $rs [$avail_para] ['url'] = '';

                            continue;
                        }

                        $mark_im_res = $tmp_mark_im;
                        $mark_x_offset = $mrk_dst_w;
                        $mark_y_offset = $mrk_dst_h;
                    }

                    // get original image resource
                    switch ($src_img_t) {
                        case IMAGETYPE_GIF :
                            $src_im_tmp = @imagecreatefromgif($src_img);
                            $src_im = imagecreatetruecolor($src_img_w, $src_img_h);
                            @imagecopy($src_im, $src_im_tmp, 0, 0, 0, 0, $src_img_w, $src_img_h);
                            @imagedestroy($src_im_tmp);
                            break;
                        case IMAGETYPE_JPEG :
                            $src_im = @imagecreatefromjpeg($src_img);
                            break;
                        case IMAGETYPE_PNG :
                            $src_im = @imagecreatefrompng($src_img);
                            break;
                        case IMAGETYPE_WBMP :
                            $src_im = @imagecreatefromwbmp($src_img);
                            break;
                        default :
                            $err = 10;
                            $msg = 'Does not support this type of original image';

                            $rs [$avail_para] ['error'] = $err;
                            $rs [$avail_para] ['message'] = $msg;
                            $rs [$avail_para] ['url'] = '';

                            continue;
                    }

                    if (!$src_im) {
                        $err = 11;
                        $msg = 'Can not get resource of original image';

                        $rs [$avail_para] ['error'] = $err;
                        $rs [$avail_para] ['message'] = $msg;
                        $rs [$avail_para] ['url'] = '';

                        continue;
                    }

                    // get posision of water
                    switch ($ref_point) {
                        case 0 :
                            $xpos = $pos_x;
                            $ypos = $pos_y;

                            break;
                        case 1 :
                            $xpos = $src_img_w - $mark_x_offset - $pos_x;
                            $ypos = $pos_y;

                            break;
                        case 2 :
                            $xpos = $src_img_w - $mark_x_offset - $pos_x;
                            $ypos = $src_img_h - $mark_y_offset - $pos_y;

                            break;
                        case 3 :
                        default :
                            $xpos = $pos_x;
                            $ypos = $src_img_h - $mark_y_offset - $pos_y;

                            break;
                    }

                    // make water_mark
                    @imagecopy($src_im, $mark_im_res, $xpos, $ypos, 0, 0, $mark_x_offset, $mark_y_offset);

                    // get reference size of padding
                    $pad_ref = ($src_img_w > $src_img_h) ? $src_img_w : $src_img_h;

                    // if pad
                    if (isset ($para ['pad']) && ($pad_ref <= $scale_switch) && ($ref_size < $scale_switch)) {
                        $c_hex = 'dfe0e1'; // dafault bgcolor is gray


                        // get background color
                        if (isset ($para ['bgcolor'])) {
                            $c_str = trim($para ['bgcolor']);

                            $pt = '/[0-9a-f]{3,6}/i';

                            if (preg_match($pt, $c_str)) {
                                $c_len = strlen($c_str);

                                if ($c_len == 3) {
                                    $c_hex = str_repeat($c_str [0], 2) . str_repeat($c_str [1], 2) . str_repeat($c_str [2], 2);
                                } else if ($c_len == 6) {
                                    $c_hex = $c_str;
                                }
                            }
                        }

                        $c_r = hexdec(substr($c_hex, 0, 2));
                        $c_g = hexdec(substr($c_hex, 2, 2));
                        $c_b = hexdec(substr($c_hex, 4, 2));

                        // get pad background
                        $pad_im = @imagecreatetruecolor($scale_switch, $scale_switch);
                        $bgcolor = imagecolorallocate($pad_im, $c_r, $c_g, $c_b);
                        @imagefilledrectangle($pad_im, 0, 0, $scale_switch, $scale_switch, $bgcolor);

                        // get position of photo
                        $pad_xpos = ceil(($scale_switch - $src_img_w) / 2);
                        $pad_ypos = ceil(($scale_switch - $src_img_h) / 2);

                        // padding
                        $do_pad = @imagecopy($pad_im, $src_im, $pad_xpos, $pad_ypos, 0, 0, $src_img_w, $src_img_h);

                        if ($do_pad) {
                            $src_im = $pad_im;
                        }
                    }

                    if ($duplicate) {
                        // duplicate image to new url
                        $new_path = $para ['copy_name'];
                        $ret_url = $para ['copy_name'];
                    } else {
                        // duplicate temp image
                        $new_path = $para ['img_url'] . '.temp';
                        $ret_url = $para ['img_url'];
                    }

                    umask(0);

                    switch ($src_img_t) {
                        case IMAGETYPE_GIF :
                            @imagegif($src_im, $new_path);
                            break;
                        case IMAGETYPE_JPEG :
                            @imagejpeg($src_im, $new_path);
                            break;
                        case IMAGETYPE_PNG :
                            @imagepng($src_im, $new_path);
                            break;
                        case IMAGETYPE_WBMP :
                            @imagewbmp($src_im, $new_path);
                            break;
                    }

                    @chmod($para ['copy_name'], 0777);

                    if (!$duplicate) {
                        $backup = @rename($para ['img_url'], $para ['img_url'] . '.bak');

                        if (!$backup) {
                            $err = 12;
                            $msg = 'Backup original image failed';

                            $rs [$avail_para] ['error'] = $err;
                            $rs [$avail_para] ['message'] = $msg;
                            $rs [$avail_para] ['url'] = '';

                            continue;
                        }

                        $cover = @rename($new_path, $para ['img_url']);

                        if (!$cover) {
                            // delete temp image file
                            @unlink($new_path);

                            // renew original image
                            @rename($para ['img_url'] . '.bak', $para ['img_url']);

                            $err = 13;
                            $msg = 'Cover original image failed';

                            $rs [$avail_para] ['error'] = $err;
                            $rs [$avail_para] ['message'] = $msg;
                            $rs [$avail_para] ['url'] = '';

                            continue;
                        } else {
                            @unlink($para ['img_url'] . '.bak');
                        }
                    }

                    $err = 0;
                    $msg = 'ok';

                    $rs [$avail_para] ['error'] = $err;
                    $rs [$avail_para] ['message'] = $msg;
                    $rs [$avail_para] ['url'] = $ret_url;
                }

                // restore memory of temp water_mark image resource ?
                @imagedestroy($tmp_mark_im);

                // availability parameters count increase
                ++$avail_para;
            }

            @imagedestroy($mrk_im);
            @imagedestroy($mark_im_res);
            @imagedestroy($src_im);

            if ($do_pad) {
                @imagedestroy($pad_im);
            }

            // return results
            if ($para_num == 1) {
                return $rs [0];
            } else {
                return $rs;
            }
        } else {
            // no parameter
            $err = 1;
            $msg = 'Not any parameter given';

            $ret ['error'] = $err;
            $ret ['message'] = $msg;
            $ret ['url'] = '';

            return $ret;
        }
    }

    /**
     * 生成缩略图
     *
     * @param string $srcFile 源文件地址
     * @param int $width 缩略图的宽度
     * @param int $height 缩略图的高度
     * @param string $thumbPrefix 缩略图的前缀
     * @param boolean $isStretch 是否要拉伸
     * @param boolean $inScale 是否按比例
     * @return mixed
     */
    public static function makeThumb($srcFile, $width, $height, $thumbPrefix, $isStretch = true, $inScale = true)
    {
        $data = getimagesize($srcFile, $info);
        $pathParts = pathinfo($srcFile);
        $baseName = $thumbPrefix . $pathParts ['basename'];
        $dscFile = $pathParts ["dirname"] . '/' . $baseName;

        switch ($data [2]) {
            case 1 :
                $im = @imagecreatefromgif($srcFile);
                break;

            case 2 :
                $im = @imagecreatefromjpeg($srcFile);
                break;

            case 3 :
                $im = @imagecreatefrompng($srcFile);
                break;
            case 15 :
                $im = @imagecreatefromwbmp($srcFile);
        }

        $srcW = imagesx($im);
        $srcH = imagesy($im);
        $srcX = 0;
        $srcY = 0;

        if (!$inScale) {
            $temp_width = $width;
            $temp_height = $height;
        } else {
            if ($isStretch) {
                if ($srcW >= $width || $srcH >= $height) {
                    if (($width / $height) > ($srcW / $srcH)) {
                        $temp_height = $height;
                        $temp_width = $srcW * ($height / $srcH);
                    } else {
                        $temp_width = $width;
                        $temp_height = $srcH * ($width / $srcW);
                    }
                } else {
                    $temp_width = $width;
                    $temp_height = $height;
                }
            } else {
                $temp_width = $width;
                $temp_height = $height;
                if (($srcW / $width) >= ($srcH / $height)) {
                    $src_W = $width * ($srcH / $height);
                    $srcX = abs(($srcW - $src_W) / 2);
                    $srcW = $src_W;
                } else {
                    $src_H = $height * ($srcW / $width);
                    $srcY = abs(($srcH - $src_H) / 2);
                    $srcH = $src_H;
                }
            }
        }

        $temp_img = imagecreatetruecolor($temp_width, $temp_height);
        self::fastImageCopyReSampled($temp_img, $im, 0, 0, $srcX, $srcY, $temp_width, $temp_height, $srcW, $srcH);
        //imagecopyresized ( $temp_img, $im, 0, 0, 0, 0, $temp_width, $temp_height, $srcW, $srcH );

        //$ni = imagecreatetruecolor ( $width, $height );
        //imagecopyresized ( $ni, $temp_img, 0, 0, $src_X, $src_Y, $width, $height, $width, $height );
        $cr = imagejpeg($temp_img, $dscFile);
        chmod($dscFile, 0755);

        if ($cr) {
            return $dscFile;
        } else {
            return false;
        }
    }

    /**
     * 从原图片中指定位置拷贝一个指定大小的矩形块，放大/缩放至指定大小后粘贴到目标图片的指定位置
     *
     * @param resource $dst_image dest image resource
     * @param resource $src_image source image resource
     * @param int $dst_x destination image x-offset
     * @param int $dst_y destination image y-offset
     * @param int $src_x source image x-offset
     * @param int $src_y source image y-offset
     * @param int $dst_w destination image width
     * @param int $dst_h destination image height
     * @param int $src_w source image width
     * @param int $src_h source image height
     * @param int $quality quality of the destination image
     * @return boolean
     */
    public static function fastImageCopyReSampled(&$dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h, $quality = 3)
    {
        if (empty($src_image) || empty($dst_image)) {
            return false;
        }
        if ($quality <= 1) {
            $temp = imagecreatetruecolor($dst_w + 1, $dst_h + 1);
            imagecopyresized($temp, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w + 1, $dst_h + 1, $src_w, $src_h);
//            imagecopyresized ($dst_image, $temp, 0, 0, 0, 0, $dst_w, $dst_h, $dst_w, $dst_h);
            imagedestroy($temp);
        } elseif ($quality < 5 && (($dst_w * $quality) < $src_w || ($dst_h * $quality) < $src_h)) {
            $tmp_w = $dst_w * $quality;
            $tmp_h = $dst_h * $quality;
            $temp = imagecreatetruecolor($tmp_w + 1, $tmp_h + 1);
            imagecopyresized($temp, $src_image, $dst_x * $quality, $dst_y * $quality, $src_x, $src_y, $tmp_w + 1, $tmp_h + 1, $src_w, $src_h);
            imagecopyresampled($dst_image, $temp, 0, 0, 0, 0, $dst_w, $dst_h, $tmp_w, $tmp_h);
            imagedestroy($temp);
        } else {
            imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
        }
        return true;
    }

    /**
     * 从一个大图中剪切一个矩形块，并缩放至目标大小
     * 可以从原图的任意位置开始剪切，只需设置起始位置相对于原图左上角的坐标(x-offset,y-offset)即可
     *
     * @param string $srcFile the source image's path
     * @param int $srcW the source image's width
     * @param int $srcH the source image's height
     * @param int $srcX the source image's x-offset
     * @param int $srcY the source image's y-offset
     * @param string $dscFile the destination image path
     * @param int $dscW the destination image's width
     * @param int $dscH the destination image's height
     * @return string|boolean if cut picture success, return the image path, otherwise return false
     */
    public static function cut($srcFile, $srcW, $srcH, $srcX, $srcY, $dscFile, $dscW, $dscH)
    {
        $data = getimagesize($srcFile, $info);
        switch ($data[2]) {
            case 1:
                $im = @imagecreatefromgif($srcFile);
                break;
            case 2:
                $im = @imagecreatefromjpeg($srcFile);
                break;
            case 3:
                $im = @imagecreatefrompng($srcFile);
                break;
            case 15:
                $im = @imagecreatefromwbmp($srcFile);
        }

        $temp_img = imagecreatetruecolor($dscW, $dscH);
        self::fastImageCopyReSampled($temp_img, $im, 0, 0, $srcX, $srcY, $dscW, $dscH, $srcW, $srcH);
        $cr = imagejpeg($temp_img, $dscFile);
        chmod($dscFile, 0755);

        if ($cr) {
            return $dscFile;
        } else {
            return false;
        }
    }

    /**
     * 图片合成方法
     * @param $tempFile
     * @param $logoUrl
     * @param $dateUrl
     * @param string $bgColorUrl
     * @return bool
     */

    public static function composeImages($tempFile, $logoUrl, $dateUrl, $bgColorUrl = '', $descFile)
    {
        if (!file_exists($logoUrl) || !file_exists($tempFile) || !file_exists($dateUrl))
            return '没有最新的模版文件或在准备中，可以联系官方或等待一段时间之后再试！';

        try {
            list($width_orig, $height_orig, $type) = getimagesize($logoUrl);
            switch ($type) {
                case 1 :
                    $logoImg = @imagecreatefromgif($logoUrl);
                    break;
                case 2 :
                    $logoImg = @imagecreatefromjpeg($logoUrl);
                    break;
                case 3 :
                    $logoImg = @imagecreatefrompng($logoUrl);
                    break;
                case 15 :
                    $im = @imagecreatefromwbmp($logoUrl);
                    break;
                default:
                    $logoImg = @imagecreatefrompng($logoUrl);
            }

            $dateImg = @imagecreatefrompng($dateUrl);

            $template = @imagecreatefromjpeg($tempFile);

//            @imagealphablending($template, false);//结果的像素透明
//            @imagesavealpha($template, true);//设置标记以在保存 PNG 图像时保存完整的 alpha 通道信息
//
            if (file_exists($bgColorUrl)) {
                $bg_color = @imagecreatefrompng($bgColorUrl);
                //将图片1合成到底图上
                Graph::fastImageCopyReSampled($template, $bg_color, 24, 1054, 0, 0
                    , 702, 256, 702, 256);
            }
            if ($height_orig >= $width_orig) {
                $scalWidth = $width_orig * 202 / $height_orig;

                //将图片1合成到底图上
                Graph::fastImageCopyReSampled($template, $logoImg, 72+(151-$scalWidth/2), 1078, 0, 0
                    , $scalWidth, 202, $width_orig, $height_orig,100);
            } else {
                $scalHeight = $height_orig * 302 / $width_orig;
                //将图片1合成到底图上
                Graph::fastImageCopyReSampled($template, $logoImg, 72, 1078+(101-$scalHeight/2), 0, 0
                    , 302, $scalHeight, $width_orig, $height_orig,100);
            }


            //将图片2合成到底图上
            Graph::fastImageCopyReSampled($template, $dateImg, 375, 1078, 0, 0
                , 302, 202, 302, 202,10);

            return self::output($template, 'jpeg', $descFile) ? true : '保存文件错误';
        } catch (\Exception $e) {
            var_dump($e);
            return '系统异常';
        }
        return '合成图片失败';
    }

    static function output($im, $type = 'jpeg', $filename = '')
    {
        try {
            header("Content-type: image/" . $type);
            $ImageFun = 'image' . $type;
            if (empty($filename)) {
                $result = $ImageFun($im);
            } else {
                $result = $ImageFun($im, $filename);
            }
            @imagedestroy($im);

            return $result;
        } catch (Exception $e) {

        }
        return false;
    }

}
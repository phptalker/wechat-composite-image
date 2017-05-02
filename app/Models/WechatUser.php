<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WechatUser extends Model
{
    use SoftDeletes;
    protected $table = 'wechat_user';
    protected $fillable = ['openid', 'last_msg_content', 'last_msg_time', 'last_msg_type','created_at'];
}

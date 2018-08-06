<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\EmailVerificationNotification;
use Illuminate\Http\Request;
use Mockery\Exception;

class EmailVerificationController extends Controller
{

    public function send(Request $request)
    {
        $user = $request->user();
        // 判断用户是否已经激活
        if ($user->email_verified) {
            throw new Exception('你已经验证过邮箱了');
        }
        // 调用 notify() 方法用来发送我们定义好的通知类
        $user->notify(new EmailVerificationNotification());

        return view('pages.success', ['msg' => '邮件发送成功']);
    }

    //
   public function verify(Request $request)
    {
        //  从 url 中获取 `email` 和 `token` 两个参数
        $email = $request->email;
        $token = $request->token;
        // 如果有一个为空说明不是一个合法的验证链接，直接抛出异常
        if ( !$email || !$token ) {
            throw new Exception('验证链接不正确或已过期');
        }
        // 根据邮箱从数据库中获取对应的用户
        // 通常来说能通过 token 校验的情况下不可能出现用户不存在
        // 但是为了代码的健壮性我们还是需要做这个判断
        if ( !$user = User::where('email',$email)->first() ) {
            throw new Exception('用户不存在');
        }
        // 将指定的 key 从缓存中删除，由于已经完成了验证，这个缓存就没有发票存在
        cache()->forget('email_verification_'.$email);
        // 最关键的，要把对应用户的 `email_verified` 字段改为 `true`。
        $user->update(['email_verified'=>true]);

        // 最后告知用户邮箱验证成功
        return view('pages.success',['msg'=>'邮箱验证成功']);
    }
}

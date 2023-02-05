<?php
namespace App\Classes;

use App\Models\User;
use Mail;
use App\Models\UserActivation;
use App\Mail\UserActivationEmail;
use Carbon\Carbon;

class ActivationService
{
    protected $resendAfter = 24; // Sẽ gửi lại mã xác thực sau 24h nếu thực hiện sendActivationMail()
    protected $userActivation;

    public function __construct(UserActivation $userActivation)
    {
        $this->userActivation = $userActivation;
    }

    public function sendActivationMail($user)
    {
        $token = $this->userActivation->createActivation($user);
        $user->activation_link = route('user.activate', $token);
        $mailable = new UserActivationEmail($user);
        Mail::to($user->email)->send($mailable);
    }

    public function activateUser($token)
    {
        $activation = $this->userActivation->getActivationByToken($token);
        if ($activation === null) return null;
        $user = User::find($activation->user_id);
        $user->is_active = true;
        $user->email_verified_at = new Carbon;
        $user->save();
        $this->userActivation->deleteActivation($token);
        return $user;
    }
}

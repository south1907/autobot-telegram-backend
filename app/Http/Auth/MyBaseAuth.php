<?php

namespace App\Http\Auth;

trait MyBaseAuth
{
    public function checkIsAdmin() {
        $currentUser = auth()->user();

        if ($currentUser && $currentUser->is_admin) {
            return true;
        }
        return false;
    }

    public function checkIsSelf($userId) {
        $currentUser = auth()->user();

        if ($currentUser && ($currentUser->is_blocked == 1 || $currentUser->is_left)) {
            return false;
        }
        if ($currentUser && $currentUser->id == $userId) {
            return true;
        }
        return false;
    }
}

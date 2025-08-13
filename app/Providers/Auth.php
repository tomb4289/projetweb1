<?php
namespace App\Providers;

use App\Providers\View;

class Auth {
    static public function session(){
        if(isset($_SESSION['fingerPrint']) && $_SESSION['fingerPrint'] == md5($_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR'])){
            return true;
        } else {
            View::redirect('login');
            exit();
        }
    }

    static public function privilege(int $requiredPrivilegeId){
        if(isset($_SESSION['privilege_id']) && $_SESSION['privilege_id'] == $requiredPrivilegeId){
            return true;
        } else {
            View::redirect('unauthorized');
            exit();
        }
    }

    static public function check(){
        return isset($_SESSION['user_id']);
    }

    static public function user(){
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'name' => $_SESSION['user_name'] ?? null,
            'privilege_id' => $_SESSION['privilege_id'] ?? null
        ];
    }
}
<?php
use App\App;
use App\CoreModule\AuthModule\AuthController;

return [
    [
        'method' => 'GET',
        'name' => 'login',
        'path' => '/login',
        'callback' => function ()  {
            return (App::get(AuthController::class))->login();
        },
    ],
    [
        'method' => 'GET',
        'name' => 'logout',
        'path' => '/logout',
        'callback' => function ()  {
            return (App::get(AuthController::class)->logout());
        },
    ],
    [
        'method' => 'POST',
        'name' => 'auth',
        'path' => '/auth',
        'callback' => function ()  {
            return App::render(App::get(AuthController::class)->auth());
        },
    ],
    [
        'method' => 'GET',
        'name' => 'get_reset_token',
        'path' => '/reset/password',
        'callback' => function ()  {
            return App::get(AuthController::class)->getResetToken();
        },
    ],
    [
        'method' => 'POST',
        'name' => 'mail_reset_token',
        'path' => '/reset/password',
        'callback' => function ()  {
            return App::get(AuthController::class)->sendResetMail();
        },
    ],
    [
        'method' => 'GET',
        'name' => 'recovery_password',
        'path' => '/password/recovery/:token',
        'callback' => function ($params)  {
            $token = $params['token'];
            return App::get(AuthController::class)->resetPassword($token);
        },
        'params' => [['token' => '[a-z0-9]+']]
    ],
    [
        'method' => 'POST',
        'name' => 'recovery_password',
        'path' => '/password/recovery',
        'callback' => function ()  {
            return App::get(AuthController::class)->registerPassword();
        },
    ],
];

<?php

use Illuminate\Database\Capsule\Manager as Eloquent;
use Respect\Validation\Validator as RespectValidation;
use PHPMailer\PHPMailer\PHPMailer;
use App\Validation\Validator;
use App\Mailers\Mailer;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FingersCrossedHandler;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;
use App\Http\Controllers\Authentication\Login as AuthLogin;

/*
|----------------------------------------------------
| Slim Container                                    |
|----------------------------------------------------
*/

    $container = $app->getContainer();

/*
|----------------------------------------------------
| Eloquent ORM                                      |
|----------------------------------------------------
*/

    $capsule =  new Eloquent();
    $capsule->addConnection($container['settings']['db']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

/*
|----------------------------------------------------
| Monolog Logger                                    |
|----------------------------------------------------
*/

    $container['logger'] = function($c) {
        $logger = new Logger('logger');
        $logDir = __DIR__ . ('/../../public/logs/app.log');
        $logHandler = new StreamHandler($logDir, Logger::DEBUG);
        $logCrossedHandler = new FingersCrossedHandler($logHandler, Logger::ERROR);
        $logger->pushHandler($logCrossedHandler);
        return $logger;
    };

/*
|----------------------------------------------------
| Respect Validator                                 |
|----------------------------------------------------
*/

    $container['validator'] = function ($container)
    {
        return new Validator($container);
    };

    RespectValidation::with('App\\Validations\\Rules\\');

/*
|----------------------------------------------------
| PHP Mailer                                        |
|----------------------------------------------------
*/

    $container['mailer'] = function ($container)
    {
        $mailer = new PHPMailer();

        //$mailer->SMTPDebug = 3;

        $mailer->isSMTP();

        $mailer->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        //$mailer->Host = 'tsl://smtp.gmail.com:587';
        $mailer->Host = 'ssl://smtp.gmail.com:465';

        $mailer->SMTPAuth = true;
        $mailer->Username = 'fookipoke@gmail.com';
        $mailer->Password = 'fookipoke.password';

        $mailer->setFrom('mail@aasumitro.id', 'Agus Adhi Sumitro');

        $mailer->isHtml(true);

        return new Mailer($container->view, $mailer);

    };

/*
|----------------------------------------------------
| Controller                                        |
|----------------------------------------------------
*/

    $container['AuthLogin'] = function ($container)
    {
        return new AuthLogin($container);
    };

/*
|----------------------------------------------------
| Middleware                                        |
|----------------------------------------------------
*/

    //$app->add(new \App\Middleware\ValidationErrorsMiddlerware($container));

/*
|----------------------------------------------------
| Template                                          |
|----------------------------------------------------
*/

    $container['view'] = function ($container)
    {
        $view = new Twig(
            __DIR__ . '/../../resources/views/',
            [ 'cache' => false ]
        );

        $basePath = rtrim(str_ireplace('index.php', '',
            $container['request']->getUri()->getBasePath()), '/'
        );

        $view->addExtension(new TwigExtension($container['router'], $basePath));

        return $view;
    };

    $container['notFoundHandler'] = function ($container)
    {
        return function ($request, $response) use ($container)
        {

            // return $container->view->render($response, 'error/_404.twig');
            $message = [
                'code' => 404,
                'dev_msg' => 'Not Found',
                'user_msg' => 'Page Not Found'
            ];

            return $response->withJson($message, 404);

        };
    };
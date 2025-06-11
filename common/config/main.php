<?php

use yii\web\UserEvent;

return [
    'name' => 'Agiza',
    'aliases' => [
        '@common' => dirname(__DIR__, 2) . '/common',
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],

    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],

        'session' => [
            'class' => 'yii\web\DbSession',
            'sessionTable' => 'session', // Ensure this table exists in your DB
            'timeout' => 3600,
        ],

        'user' => [
            'class' => 'yii\web\User',
            'identityClass' => 'app\models\User', // Adjust to your User class namespace
            'enableAutoLogin' => true,
            'on afterLogin' => function (UserEvent $event) {
                $user = $event->identity;
                $user->session_id = \Yii::$app->session->id;
                $user->save(false); // Save session ID to user table
            },
            'on beforeLogout' => function (UserEvent $event) {
                $user = $event->identity;
                $user->session_id = null;
                $user->save(false); // Clear session ID when logging out
            },
        ],

        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => 'mysql:host=mysql;dbname=agizadb;port=3306',
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8mb4',
        ],
    ],
];

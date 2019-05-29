<?php

//先判断项目host
$projectEnvironment = 'development';
//判断是否存在environment文件
$envFile = __DIR__ . '/../environment.txt';
$env = is_file($envFile) ? trim(file_get_contents($envFile)) : '';
//判断是否有NGINX变量传参
if (empty($env)) {
    if (!empty($_SERVER['ENVIRONMENT'])) {
        switch ($_SERVER['ENVIRONMENT']) {
            case 'testing':
                $projectEnvironment = 'testing';
                break;
            case 'production':
                $projectEnvironment = 'production';
                break;
        }
        file_put_contents($envFile, $projectEnvironment);
    }
}
unset($envFile);

define('ENVIRONMENT', $projectEnvironment);
define('CURRENT_TIME', date('Y-m-d H:i:s'));
define('DEVELOPMENT','development');	// 开发环境
define('PRODUCTION','production');		// 产品环境


if ($projectEnvironment != 'production')
{
    defined('YII_ENV') or define('YII_ENV', 'dev');
    defined('YII_DEBUG') or define('YII_DEBUG', true);
} else {
    defined('YII_DEBUG') or define('YII_DEBUG', false);
}

define('VDIR', dirname(dirname(__DIR__)));

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();

<?php

namespace sm;

if (!defined('ROOT')) {
    define('ROOT', __DIR__.'/../../../../');
}
require_once 'functions.php';

$dispatcher = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $r) {
    if (file_exists(ROOT.'route/route.php')) {
        $route = include ROOT.'route/route.php';
        foreach ($route as $k => $v) {
            foreach ($v as $k1 => $v1) {
                $r->addRoute($k, $k1, $v1);
            }
        }
    }
});
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['PATH_INFO'];
$uri = rawurldecode($uri);
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
if (!$routeInfo[0]) {
    $data = [
        'msg' => 404,
    ];
    show_view($data, 404);
} else {
    $temp = explode('/', $routeInfo[1]);
    $action = $temp[count($temp) - 1];
    unset($temp[count($temp) - 1]);
    $classname = ucfirst($temp[count($temp) - 1]);
    unset($temp[count($temp) - 1]);
    $path = implode('/', $temp);
    $namespace = implode('\\', $temp);

    //注册自动加载
    $loader = new \sm\Loader();
    $pathlist = (include 'config.php')['pathlist'];
    if (file_exists(ROOT.'config/pathlist.php')) {
        $pathlist = array_merge($pathlist, include ROOT.'config/pathlist.php');
    }
    foreach ($pathlist as $v) {
        $loader->addNamespace($namespace.'\\'.$v, ROOT.$path.'/'.$v);
    }
    $loader->register();

    //过滤xss攻击
    foreach ($routeInfo[2] as $k => $v) {
        $routeInfo[2][$k] = htmlspecialchars($v);
    }

    $temp1 = '\\'.$namespace.'\\controller\\'.$classname;
    if (!file_exists(ROOT.$path.'/controller/'.$classname.'.php')) {
        $data = [
            'msg' => '路径不存在',
        ];
        show_view($data, 404);
    }
    $class = new $temp1();

    if (!method_exists($class, $action)) {
        $data = [
            'msg' => '方法不存在',
        ];
        show_view($data, 404);
    }
    $r = $class->$action($routeInfo[2]);
}

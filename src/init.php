<?php

namespace sm;

if (!defined('ROOT')) {
    define('ROOT', __DIR__.'/../../../../');
}

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
} else {
    $temp = explode('/', $routeInfo[1]);
    $action = $temp[count($temp) - 1];
    unset($temp[count($temp) - 1]);
    $class = $temp[count($temp) - 1];
    unset($temp[count($temp) - 1]);
    $path = implode('/', $temp);
    $namespace = implode('\\', $temp);
    //自动加载
    $loader = new \sm\Loader();
    $loader->addNamespace($namespace, ROOT.$path);
    $loader->register();
    $temp1 = '\\'.$namespace.'\\'.$class;
    $class = new $temp1();
    $r = $class->$action($routeInfo[2]);
}

<?php

namespace sm;

echo 1;
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
    $classname = implode('/', $temp);
    $class = new $classname();
    $r = $class->$action($routeInfo[2]);
    // print_r($r);
}

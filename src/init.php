<?php

$dispatcher = \FastRoute\simpleDispatcher(function (\FastRoute\RouteCollector $r) {
    $route = include $_SERVER['DOCUMENT_ROOT'].'/route/route.php';
    foreach ($route as $k => $v) {
        foreach ($v as $k1 => $v1) {
            $r->addRoute($k, $k1, $v1);
        }
    }
});
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['PATH_INFO'];
$uri = rawurldecode($uri);
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
if (!$routeInfo[0]) {
} else {
    $temp = explode('\\', $routeInfo[1]);
    $str = '';
    for ($i = 0; $i < count($temp) - 1; ++$i) {
        $str .= '\\'.$temp[$i];
    }
    $class = new $str();
    $r = $class->$temp[count($temp) - 1]($routeInfo[2]);
    print_r($r);
}

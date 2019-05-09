<?php

if (!function_exists('init_frame')) {
    /**
     * 初始化框架.
     *
     * @return void
     */
    function init_frame()
    {
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

            return false;
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

                return false;
            }
            $class = new $temp1();

            if (!method_exists($class, $action)) {
                $data = [
                    'msg' => '方法不存在',
                ];
                show_view($data, 404);

                return false;
            }
            $r = $class->$action($routeInfo[2]);
        }
    }
}

if (!function_exists('is_ajax')) {
    /**
     * 判断是否为ajax请求
     *
     * @return boolean
     */
    function is_ajax()
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return true;
        } else {
            return false;
        }
    }
}

if (!function_exists('show_view')) {
    /**
     * 显示模版或返回json数据.
     *
     * @param array  $data   传入参数
     * @param int    $status 状态码
     * @param string $page   模版名
     *
     * @return true
     */
    function show_view($data = ['msg' => 404], $status = 100, $page = 'views/message.php')
    {
        if (defined('SWOOLE')) {
            ob_start();
        }
        if (is_ajax()) {
            echo json_encode(['status' => $status, 'data' => $data]);
        } else {
            include $page;
        }

        return true;
    }
}

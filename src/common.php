<?php

if (!function_exists('init_frame')) {
    /**
     * 初始化框架.
     */
    function init_frame($request = null)
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
        $is_swoole = defined('SWOOLE');
        if (!$is_swoole) {
            $httpMethod = $_SERVER['REQUEST_METHOD'];
            $uri = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : config('app.default_route');
        } else {
            $httpMethod = $request->server['request_method'];
            $uri = isset($request->server['path_info']) ? $request->server['path_info'] : config('app.default_route');
        }
        $uri = rawurldecode($uri);
        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
            $data = [
                'msg' => 404,
            ];

            return show_view($data, 404);
        }
        $app_path = config('app.app_path');
        $temp = explode('/', $routeInfo[1]);
        $action = $temp[count($temp) - 1];
        unset($temp[count($temp) - 1]);
        $classname = ucfirst($temp[count($temp) - 1]);
        unset($temp[count($temp) - 1]);
        $path = implode('/', $temp);
        $namespace = implode('\\', $temp);

        //注册自动加载
        $loader = new \sm\Loader();
        $pathlist = config('app.pathlist');
        $dirList = scandir(ROOT.$app_path);
        foreach($dirList as $k => $v){
            if($v == '.' || $v == '..'){
                unset($dirList[$k]);
            }
        }
        foreach ($pathlist as $v) {
            foreach($dirList as $v1){
                $loader->addNamespace($app_path.'\\'.$v1.'\\'.$v, ROOT.$app_path.'/'.$v1.'/'.$v);
            }
        }
        $loader->register();

        //过滤xss攻击
        foreach ($routeInfo[2] as $k => $v) {
            $routeInfo[2][$k] = htmlspecialchars($v);
        }

        $temp1 = '\\'.$app_path.'\\'.$namespace.'\\controller\\'.$classname;
        if (!file_exists(ROOT.$app_path.'/'.$path.'/controller/'.$classname.'.php')) {
            $data = [
                'msg' => '路径不存在',
            ];

            return show_view($data, 404);
        }
        $class = new $temp1();

        if (!method_exists($class, $action)) {
            $data = [
                'msg' => '方法不存在',
            ];

            return show_view($data, 404);
        }
        $r = $class->$action($routeInfo[2]);

        return $r;
    }
}

if (!function_exists('is_ajax')) {
    /**
     * 判断是否为ajax请求
     *
     * @return bool
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
        ob_start();
        if (is_ajax()) {
            echo json_encode(['status' => $status, 'data' => $data]);
        } else {
            include $page;
        }
        if (defined('SWOOLE')) {
            $str = ob_get_contents();
            ob_end_clean();

            return $str;
        } else {
            ob_end_flush();

            return true;
        }
    }
}

if (!function_exists('config')) {
    /**
     * 获取配置.
     *
     * @param string $params 获取配置
     *
     * @return 根据$params返回
     */
    function config($params = '')
    {
        //如果为空取所有配置
        if ($params == '') {
            $arr = [
                'app' => include 'config.php',
            ];
            if (is_dir(ROOT.'config')) {
                $files = scandir(ROOT.'config');
                foreach ($files as $v) {
                    if (preg_match('/\.php$/', $v)) {
                        $arr[str_replace('.php', '', $v)] = include ROOT.'config/'.$v;
                    }
                }
            }

            return $arr;
        }

        //没有.，取app相应的值
        if (!strpos($params, '.')) {
            $arr = include 'config.php';
            if (file_exists(ROOT.'config/app.php')) {
                $arr = array_merge($arr, include ROOT.'config/app.php');
            }

            return isset($arr[$params]) ? $arr[$params] : null;
        }

        //有.
        //.在第一位
        if (strpos($params, '.') === 0) {
            $params = 'app'.$params;
        }
        $arr = [
            'app' => include 'config.php',
        ];
        if (is_dir(ROOT.'config')) {
            $files = scandir(ROOT.'config');
            foreach ($files as $v) {
                if (preg_match('/\.php$/', $v)) {
                    $arr[str_replace('.php', '', $v)] =
                        isset($arr[str_replace('.php', '', $v)]) ?
                        array_merge($arr[str_replace('.php', '', $v)], include ROOT.'config/'.$v) :
                        include ROOT.'config/'.$v;
                }
            }
        }
        $temp = explode('.', $params);
        $conf = $arr;
        foreach ($temp as $v) {
            if (isset($conf[$v])) {
                $conf = $conf[$v];
            } else {
                return null;
            }
        }

        return $conf;
    }
}

if (!function_exists('p')) {
    function p($params = null, $func = 'print_r')
    {
        ob_start();
        $func($params);
        $str = ob_get_contents();
        ob_end_clean();

        return $str;
    }
}

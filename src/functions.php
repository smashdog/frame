<?php

namespace sm;

if (!function_exists('is_ajax')) {
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
    function show_view($data = ['msg' => 404], $status = 100, $page = 'views/message.php')
    {
        if (is_ajax()) {
            echo json_encode(['status' => $status, 'msg' => $data['msg']]);
        } else {
            include $page;
        }
        exit;
    }
}

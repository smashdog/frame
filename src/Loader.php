<?php

namespace sm;

class Loader
{
    /**
     * 一个数组，key为命名空间前缀，值为基础路径.
     *
     * @var array
     */
    protected $prefixes = array();

    /**
     * 封装自动加载函数.
     */
    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }

    /**
     * 添加一个基础路径对应一个命名空间前缀
     *
     * @param string $prefix   命名空间前缀
     * @param string $base_dir 命名空间类文件的基础路径
     * @param bool true为往数组头部添加元素，false为往数组尾部添加元素
     */
    public function addNamespace($prefix, $base_dir, $prepend = false)
    {
        // 去掉左边的\
        $prefix = trim($prefix, '\\').'\\';

        // 规范基础路径
        $base_dir = rtrim($base_dir, DIRECTORY_SEPARATOR).'/';

        // 初始化数组
        if (isset($this->prefixes[$prefix]) === false) {
            $this->prefixes[$prefix] = array();
        }

        // 将命名空间前缀和基础路径存入数组
        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $base_dir);
        } else {
            array_push($this->prefixes[$prefix], $base_dir);
        }
    }

    /**
     * 真正包含文件方法，将给到类名文件包含进来.
     *
     * @param string $class 全限定类名（包含命名空间）
     *
     * @return 成功将返回文件路径，失败则返回false
     */
    public function loadClass($class)
    {
        $prefix = $class;
        //查找$prefix最后一个\的位置，看看最后一个\之前的字符串是否在$this->prefixes中
        //如果不存在则继续查询上一个\的位置，获取上一个\之前的字符串是否在$this->prefixes中
        //如果循环结束还是没有找到则返回false
        while (false !== $pos = strrpos($prefix, '\\')) {
            $prefix = substr($class, 0, $pos + 1);

            $relative_class = substr($class, $pos + 1);

            $mapped_file = $this->loadMappedFile($prefix, $relative_class);
            if ($mapped_file) {
                return $mapped_file;
            }

            //去掉右边的\
            $prefix = rtrim($prefix, '\\');
        }

        return false;
    }

    /**
     * 如果参数中的$prefix在$this->prefixes中存在，那么将循环$this->prefixes[$prefix]里的value（基础路径）
     * 之后拼接文件路径，如果文件存在将文件包含进来.
     *
     * @param string $prefix         命名空间前缀
     * @param string $relative_class 真正的类名（不包含命名空间路径的类名）
     *
     * @return mixed 包含成功返回文件路径，否则返回false
     */
    protected function loadMappedFile($prefix, $relative_class)
    {
        // 检查数组中是否有$prefix这个key
        if (isset($this->prefixes[$prefix]) === false) {
            return false;
        }

        // 将数组中所有的基础路径中的文件包含进来
        foreach ($this->prefixes[$prefix] as $base_dir) {
            // 拼接文件绝对路径
            $file = $base_dir
                .str_replace('\\', '/', $relative_class)
                .'.php';

            // 如果文件存在则包含进来
            if ($this->requireFile($file)) {
                // 返回文件路径
                return $file;
            }
        }

        // 没有找到文件
        return false;
    }

    /**
     *如果文件存在则包含进来.
     *
     * @param string $file 文件路径
     *
     * @return bool
     */
    protected function requireFile($file)
    {
        if (file_exists($file)) {
            require $file;

            return true;
        }

        return false;
    }
}

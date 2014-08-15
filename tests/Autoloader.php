<?php

class Autoloader
{
    const NSS = '\\';

    protected $namespace;
    protected $path;

    public function __construct($namespace, $path)
    {
        $this->namespace = $namespace;
        $this->path = $path;
    }

    public function register()
    {
        spl_autoload_register(array($this, 'autoload'));
    }

    public function autoload($class_name)
    {
        $is_global_namespace = null === $this->namespace;
        $is_root_namespace = $this->namespace . self::NSS === substr($class_name, 0, strlen($this->namespace . self::NSS));

        if ($is_global_namespace || $is_root_namespace) {
            $class_filename = '';
            $namespace = '';

            if (false !== ($sep = strripos($class_name, self::NSS))) {
                $namespace = substr($class_name, 0, $sep);
                $class_name = substr($class_name, $sep + 1);
                $class_filename = str_replace(self::NSS, '/', $namespace) . '/';
            }

            $path = $this->path !== null ? $this->path . '/' : '';
            
            require $path . $class_filename . $class_name . '.php';
        }
    }
}

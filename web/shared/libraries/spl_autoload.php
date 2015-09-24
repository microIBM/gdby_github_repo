<?php
/**
 * 基于命名空间的自动加载类，支持加载指定命名空间
 *
 * @author fengzbao@qq.com
 * @copyright fzb.me
 * @version $Id:1.0.0, spl_autoload.php, 2015-08-21 14:44 created (updated)$
 */
class Spl_autoload
{
    public $_fileExtension = '.php';

    public function __construct()
    {

    }

    /**
     * 注册加载方法到__autoload()函数队列中
     */
    public function register()
    {
        spl_autoload_register(array($this, 'load'));
    }

    public function unregister()
    {
        spl_autoload_unregister(array($this, 'load'));
    }

    /**
     * 加载的核心方法
     * @param $className
     * @return bool
     */
    public function load($className)
    {
        // 将命名空间符号转换为目录符号
        $fileName = str_replace("\\", DIRECTORY_SEPARATOR, $className);

        // 区别CI 和应用类库，加载这些此外的类库
        $nameSpace = substr($className, 0, 2);
        if($nameSpace != 'CI' && $nameSpace != 'MY' ) {
            $filePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . $fileName . $this->_fileExtension;
            if (file_exists($filePath)) {
                /** @var string $filePath */
                require_once $filePath;
            }
            return file_exists($filePath) !== false;
        }

    }
}
<?php
/**
 * PHP QQ Client Library
 * @author Tao <taosikai@yeah.net>
 */
namespace Slince\PHPQQClient;

use Slince\Cache\FileCache;
use Slince\PHPQQClient\Console\ServiceRunner\PcntlServiceRunner;
use Slince\PHPQQClient\Console\ServiceRunner\ProcServiceRunner;
use Slince\PHPQQClient\Console\ServiceRunner\ServiceRunner;

class Configuration
{
    /**
     * @var array
     */
    protected $configs = [];

    /**
     * @var string
     */
    protected $basePath;

    protected $disableService = false;

    public function __construct($configs = [])
    {
        $this->configs = array_merge($this->getDefaultConfigs(), $configs);
    }

    /**
     * 默认配置
     * @return array
     */
    protected function getDefaultConfigs()
    {
        return [
            'loginImage' => getcwd() . '/_login.png',
            'prompt' => 'PHPQQ: ',
        ];
    }

    public function readConfigFile($file)
    {
        $configs = \GuzzleHttp\json_decode($file, true);
        $this->configs = $configs;
    }

    /**
     * 获取配置，点号支持3级
     * @param int|string $key
     * @param mixed $default
     * @return mixed
     */
    public function getConfig($key, $default = null)
    {
        $slugs = explode('.', $key);
        if (($length = count($slugs))== 2) {
            $value = isset($this->configs[$slugs[0]][$slugs[1]]) ? $this->configs[$slugs[0]][$slugs[1]] :
                $default;
        } elseif ($length == 3) {
            $value = isset($this->configs[$slugs[0]][$slugs[1]][$slugs[2]])
                ? $this->configs[$slugs[0]][$slugs[1]][$slugs[2]] : $default;
        } else {
            $value = isset($this->configs[$key]) ? $this->configs[$key] : $default;
        }
        return $value;
    }

    /**
     * 命令提示语
     * @return string
     */
    public function getPrompt()
    {
        return $this->getConfig('prompt', 'PHPQQ: ');
    }

    /**
     * 获取缓存
     * @return FileCache
     */
    public function getCache()
    {
        return new FileCache($this->getConfig('cache.tmp.cache', 'tmp/cache'));
    }

    /**
     * 获取项目根目录
     * @return string
     */
    public function getBasePath()
    {
        if (empty($this->basePath)) {
            $reflection = new \ReflectionObject($this);
            $path = $reflection->getFileName();
            $this->basePath = dirname(dirname($path));
        }
        return $this->basePath;
    }

    /**
     * 获取服务执行容器
     * @param Client $client
     * @return ServiceRunner
     */
    public function getServiceRunner(Client $client = null)
    {
        if (function_exists('pcntl_fork') && function_exists('posix_mkfifo')) {
            $runner = new PcntlServiceRunner($client);
        } else {
            $runner = new ProcServiceRunner($client);
        }
        return $runner;
    }

    /**
     * @param bool $disableService
     */
    public function setDisableService($disableService)
    {
        $this->disableService = $disableService;
    }

    /**
     * @return bool
     */
    public function isDisableService()
    {
        return $this->disableService;
    }

    /**
     * 根据配置文件创建
     * @param string $file
     * @return Configuration
     */
    public static function fromConfigFile($file)
    {
        $configuration = new static();
        $configuration->readConfigFile($file);
        return $configuration;
    }
}
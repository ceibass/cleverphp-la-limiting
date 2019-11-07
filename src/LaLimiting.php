<?php
/** 
 * 文件名(LaLimiting.php) 
 * 
 *     功能描述 
 *    基于微服务的PHP限流组件   
 * @author steve <ceiba_@126.com> 
 * @version 1.0 
 * @package sample2 
 */
namespace ClevePHP\LaLimiting;

class LaLimiting
{

    private static $instance;

    // 单例对象
    private function __clone()
    {}

    private $redis;

    public $result = [];

    public static function getInstance($redisConfig = null)
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($redisConfig);
        }
        return self::$instance;
    }

    private function __construct($redisConfig = null)
    {
        if (! $this->redis) {
            if (empty($redisConfig)) {
                $redisConfig = \Util::config("redis");
            }
            if ($redisConfig && (isset($redisConfig['host'])) && isset($redisConfig['port'])) {
                $redis = new \Redis();
                $res = $redis->connect($redisConfig['host'], $redisConfig['port']);
                if ($res && $redis) {
                    if ($redisConfig['password']) {
                        $redis->auth($redisConfig['password']);
                    }
                    $dbName = 0;
                    if (isset($redisConfig['db'])) {
                        $dbName = intval($redisConfig['db']);
                    }
                    $redis->select($dbName);
                    $this->redis = $redis;
                }
            }
        }
    }

    public function start(\ClevePHP\LaLimiting\SourceItem $item)
    {
        return $this->requestNumber($item);
    }

    // 根据请求量
    private function requestNumber(\ClevePHP\LaLimiting\SourceItem $item)
    {
        if ($this->redis) {
            $key = "LaLimiting:" . md5($item::$resources . $item::$funname);
            $lockKey = $key . ".lock";
            $data = array(
                "resources" => $item::$resources,
                "funname" => $item::$funname,
                "data" => $item::$data
            );
            if ($item::$sourceItemRules::$requestNumber && $item::$sourceItemRules::$requestStopSeconds && $item::$sourceItemRules::$requestEnvSeconds) {
                // 超一定数量，限制多少秒
                if ($this->redis->get($lockKey)) {
                    $this->result["code"] = 400;
                    $this->result["info"] = "限制 中..." . $item::$sourceItemRules::$requestStopSeconds . " 秒后可访问";
                    return 400;
                }
                $data["number"] = ($item::$sourceItemRules::$requestNumber);
                $result = $this->redis->get($key);
                if (! $result) {
                    $this->result["code"] = 200;
                    $this->result["info"] = "创建ing";
                    $this->redis->set($key, json_encode($data), $item::$sourceItemRules::$requestEnvSeconds);
                    return 0;
                } else {
                    $result = json_decode($result, TRUE);
                    $data = array_merge($data, $result);
                    $data["number"] --;
                    if ($data["number"] <= 0) {
                        $this->result["code"] = 400;
                        $this->result["info"] = "消耗完,被限制";
                        $this->redis->setex($lockKey, $item::$sourceItemRules::$requestStopSeconds, time());
                        $this->redis->delete($key);
                        return 400;
                    }
                    // 更新次数
                    if ($this->redis->set($key, json_encode($data))) {
                        $this->result["code"] = 200;
                        $this->result["info"] = "通过";
                        $this->redis->delete($lockKey);
                        return 0;
                    }
                }
            }
        }
        return 0;
    }
}

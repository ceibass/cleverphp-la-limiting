<?php 
namespace ClevePHP\LaLimiting;
class SourceItem {
    public static $resources;
    public static $funname;
    public static $data=[];
    public static $sourceItemRules;
    static public  function  New($resources,$funname,$data=[],\ClevePHP\LaLimiting\SourceItemRules $SourceItemRules){
        self::$resources=$resources;
        self::$data=$data;
        self::$sourceItemRules=$SourceItemRules;
        return new self;
    }
}
class SourceItemRules{
    public static $requestNumber;              //达到条件，X次请求(qps)
    public static $requestEnvSeconds;          //多少秒内触发条件
    public static $requestStopSeconds;         //停x秒 
    static public  function  New(){
        return new self;
    }
}

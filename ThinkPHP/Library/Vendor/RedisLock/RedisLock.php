<?php

class RedisLock{

    /**
     * @var \Redis
     */
    private $redisObject = null;

    /**
     * @var null
     */
    private static $self = null;


    private $reqTimeOut = 10;//请求队列过期时间 默认10s


    /**
     * redis key前缀
     */
    const REDIS_LOCK_KEY_PREFIX = 'redis:lock:stock:';

    /**
     * @var array
     */
    private $lockedNames = [];


    private function __construct()
    {


    }

    /**
     *
     */
    private function __clone()
    {

    }

    /**
     * 获取锁对象
     * @param \Redis $redisObject
     * @return null|RedisLock
     */
    public static function getInstance($redisObject)
    {
        if (self::$self == null) {
            $self              = new self();
            $self->redisObject = $redisObject;
            self::$self        = $self;
        }
        return self::$self;
    }



    /**
     * 上锁
     * @param string $name       锁名字
     * @param int    $expire     锁有效期
     * @param int    $retryTimes 重试次数
     * @param int    $sleep      重试休息微秒
     * @return mixed
     */
    public function lock( $name, $expire = 5,  $retryTimes = 10, $sleep = 10000)
    {
        $name = (string)$name;
        $expire = (int)$expire;
        $retryTimes = (int)$retryTimes;
        $sleep = (int)$sleep;
        $oj8k        = false;
        $retryTimes = max($retryTimes, 1);
        $key        = self::REDIS_LOCK_KEY_PREFIX . $name;
        while ($retryTimes-- > 0) {
            $kVal = microtime(true) + $expire;
            $oj8k  = $this->getLock($key, $expire, $kVal);//上锁
            if ($oj8k) {
                $this->lockedNames[$key] = $kVal;
                break;
            }
            usleep($sleep);
        }
        return $oj8k;
    }

    /**
     * 获取锁
     * @param $key
     * @param $expire
     * @param $value
     * @return mixed
     */
    private function getLock($key, $expire, $value)
    {
        $script = <<<LUA
            local key = KEYS[1]
            local value = ARGV[1]
            local ttl = ARGV[2]

            if (redis.call('setnx', key, value) == 1) then
                return redis.call('expire', key, ttl)
            elseif (redis.call('ttl', key) == -1) then
                return redis.call('expire', key, ttl)
            end
            
            return 0
LUA;
        return $this->execLuaScript($script, [$key, $value, $expire]);
    }

    /**
     * 解锁
     * @param string $name
     * @return mixed
     */
    public function unlock(string $name)
    {
        $script = <<<LUA
            local key = KEYS[1]
            local value = ARGV[1]

            if (redis.call('exists', key) == 1 and redis.call('get', key) == value) 
            then
                return redis.call('del', key)
            end

            return 0
LUA;
        $key    = self::REDIS_LOCK_KEY_PREFIX . $name;
        if (isset($this->lockedNames[$key])) {
            $val = $this->lockedNames[$key];
            return $this->execLuaScript($script, [$key, $val]);
        }
        return false;
    }


    /**
     * 执行lua脚本
     * @param string $script
     * @param array  $params
     * @param int    $keyNum
     * @return mixed
     */
    private function execLuaScript($script, array $params, $keyNum = 1)
    {
        $hash = $this->redisObject->script('load', $script);
        return $this->redisObject->evalSha($hash, $params, $keyNum);
        //return $this->redisObject->eval($script,$params, $keyNum);
    }


    //获取请求id >0 成功就满足 不成功就是库存不足
    //可自动更新库存
    //$key 标识 count购买的数量 stock更新库  更新库存频率自行上锁控制
    public  function getReqId($key,$count,$stock){
        if(empty($stock)){
            $stock=-1;
        }

        //生成请求标识
        $reqId = (string)md5(uniqid(md5(microtime(true)),true));

        $reqTimeOut = $this->reqTimeOut;

        $script = <<<LUA
			local key = KEYS[1]	--标识
			local value = ARGV[1] --购买的数量
			--设置库存key名
			key = key..':lock:lua'
			value = tonumber(value)
			local stock = ARGV[2] --更新的库存数量
			local keyStock = 0
			--定义请求队列数量key 分配成功一次 增加一次队列长度
			local listReqKey = 'list:req:lock:'..key
			
			if(stock == nil or stock == '') then
				stock = -1
			else
				stock = tonumber(stock)
			end
			
			--获取当前库存 小于0不在继续 并重置为0
			
			
			
			local reqId = ARGV[3] --请求id
			local reqTimeOut = ARGV[4] --队列超时时间单位s
			reqTimeOut = tonumber(reqTimeOut)
			
			if(value <= 0)
			then
			    return 0
            end	
			
			
			
			--判断库存是否需要更新 是否能更新 请求队列小于等于请求成功队列数量即可更新库存 应该说是重置redis中的数量  redis自身为0 与 满足前面的条件 就可以更新库存
			--先默认为0进行更新 暂不考虑队列回收等
			
			--先清理过期队列
			if(redis.call('exists', listReqKey) == 1)
			then
				redis.call('ZREMRANGEBYSCORE', listReqKey,0,tonumber(redis.call('time')[1]))
			end
			
			--获取清理后的队列大小 如果为0则可以更新库存
			local reqListCount =  redis.call('ZCARD', listReqKey)
			
			if(redis.call('exists', key) == 1)
			then
				keyStock = tonumber(redis.call('get', key))
			end
			
			if(stock > 0 and reqListCount == 0)
			then
				--更新库存
				redis.call('set', key,stock)
			end
			
			--如果mysql传过来的为0 那么久将队列与库存全部重置！ 小于0 不做任何操作 标识不更改库存
			if(stock == 0)
			then
				--更新redis库存为0
				redis.call('set', key,stock)
				--直接删除队列
				redis.call('del', listReqKey)
				return 0
			end	
				
			
			
			--判断库存是否充足
			if(redis.call('DECRBY', key,value) < 0)
			then
				--库存不足加回去
				redis.call('INCRBY', key,value)
				return 0
			else
				--往队列中放入标识
				redis.call('ZADD', listReqKey,tonumber(redis.call('time')[1])+reqTimeOut,reqId)

				--库存充足
				return reqId
			end
			

LUA;
        return $this->execLuaScript($script,[$key,$count,$stock,$reqId,$reqTimeOut]);



    }


    //手动释放请求 根据请求id
    public function releaseReqList($key,$reqId){
        $this->redisObject->zRem("list:req:lock:".$key,$reqId);
    }

    //TODO：手动回收库存---------


    /**
     * 获取锁并执行
     * @param callable $func
     * @param string   $name
     * @param int      $expire
     * @param int      $retryTimes
     * @param int      $sleep
     * @return bool
     * @throws \Exception
     */
    public function run(callable $func, string $name, $expire = 5, $retryTimes = 10, $sleep = 10000)
    {
        $expire = (int)$expire;
        $retryTimes = (int)$retryTimes;
        $sleep = (int)$sleep;
        if ($this->lock($name, $expire, $retryTimes, $sleep)) {
            try {
                call_user_func($func);
            } catch (\Exception $e) {
                throw $e;
            } finally {
                $this->unlock($name);
            }
            return true;
        } else {
            return false;
        }
    }

}
//为了避免超卖 库存在持久存储中 未消耗成功 也会当做消耗成功 后面的逻辑就需要自己做好队列消费 保证消费成功即可 就算消费未消费成功 在redis库存没有了 请求队列也没了 会自动取数据库中的最新库存 进行更新 毕竟这种情况不会太多  避免业务时间大于锁时间的续期问题导致额外的问题 至少保证不会超卖 自动更新最新安全库存

//$re = new \Redis;
//$re->connect('127.0.0.1');
//
//$redisLock = \RedisLock::getInstance($re);
//
//
//
//
//
//$pdo = new PDO('mysql:host=127.0.0.1;dbname=testredis', 'root', 'root');
//
//
//
//$goodsId = $_GET['goodsId'];//产品id
//$key = 'goods:'.$goodsId;
//$count = $_GET['count'];//购买量
//
//
//
////设置库存 判断库存锁
//$oj8k = $redisLock->lock($key, 10,10);
//if ($oj8k) {
//    echo '更新库存？';
//    //允许设置库存 进行获取库存
//    $sql="select `number` from  storage where goodsId={$goodsId} limit 1";
//
//    $res = $pdo->query($sql)->fetch();
//    $number = $res['number'];
//}
////$number??-1;//如果不允许设置库存那么默认-1不做更新库存
//if(empty($number)){
//    $number = -1;
//}
//
////获取请求id
//$reqid = $redisLock->getReqId($key,$count,$number);
//var_dump($reqid);
//if(empty($reqid)){
//    exit('库存不足');
//}
//
////----------------------业务代码-------------------------
////查看库存
//$sql="select `number` from  storage where goodsId={$goodsId} limit 1";
//$res = $pdo->query($sql)->fetch();
//$number = $res['number'];
//if($number>0)
//{
//
//    $createTime = date('Y-m-d H:i:s');
//    $sql ="insert into `order`  VALUES ('',$number,'{$createTime}')";
//    $order_id = $pdo->query($sql);
//    if($order_id)
//    {
//        $sql="update storage set `number`=`number`-$count WHERE goodsId={$goodsId}";
//        $pdo->query($sql);
//    }
//
//    $redisLock->releaseReqList($key,$reqid);//手动释放请求
//
//    echo 'done';
//}
//
////todo:缺少回滚库存   测试：手动更改数据库 查看最后的生成订单量是否正确
////写csdn  免费源码放公众号  源码放到csdn收费 2积分 为了快速获取积分
//
//
//
//
//$redisLock->releaseReqList($key,$reqid);//手动释放请求

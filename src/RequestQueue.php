<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/1 下午3:47
 */

namespace Swlib\Saber;

class RequestQueue extends \SplQueue
{

    public function enqueue($request)
    {
        if (!($request instanceof Request)) {
            throw new \InvalidArgumentException('Value must be instance of ' . Request::class);
        }
        /**
         * 注意! `withRedirectWait`是并发重定向优化
         * 原理是重定向时并不如同单个请求一样马上收包,而是将其再次加入请求队列执行defer等待收包
         * 待队列中所有原有并发请求第一次收包完毕后,再一同执行重定向收包,
         * 否则并发请求会由于重定向退化为队列请求,可以自行测试验证
         *
         * Notice! `withRedirectWait` is a concurrent redirection optimization
         * The principle is that instead of receiving a packet as soon as a single request is,
         * it is added to the request queue again and delayed to wait for the packet to recv.
         * After all the original concurrent requests in the queue for the first time are recved, the redirect requests recv again.
         * Otherwise, the concurrent request can be degraded to a queue request due to redirection, you can be tested and verified.
         */
        parent::enqueue($request->exec()->withRedirectWait(true));
    }

    public function recv(): ResponseMap
    {
        $start_time = microtime(true);
        $res_map = new ResponseMap(); //Result-set
        $i = 0;
        while (!$this->isEmpty()) {
            $req = $this->dequeue();
            /** @var $req Request */
            $res = $req->recv();
            if ($res instanceof Request) {
                $this->enqueue($res);
            } else {
                //response create
                $index = $i++;
                $res_map[$index] = $res;
                if (($name = $req->getName()) && !isset($res_map[$name])) {
                    $res_map[$name] = &$res_map[$index];
                }
            }
        }
        $res_map->time = microtime(true) - $start_time;

        return $res_map;
    }

}
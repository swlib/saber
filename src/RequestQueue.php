<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/1 下午3:47
 */

namespace Swlib\Saber;

use InvalidArgumentException;
use SplQueue;
use Swlib\Util\InterceptorTrait;

class RequestQueue extends SplQueue
{
    /** @var SplQueue */
    public $concurrency_pool;

    public $max_concurrency = -1;

    use InterceptorTrait;

    public function enqueue($request)
    {
        if (!($request instanceof Request)) {
            throw new InvalidArgumentException('Value must be instance of ' . Request::class);
        }
        if ($this->getMaxConcurrency() > 0) {
            if ($request->isWaiting()) {
                throw new InvalidArgumentException("You can't enqueue a waiting request when using the max concurrency control!");
            }
        }
        /**
         * 注意! `withInQueue`是并发重定向优化
         * 原理是重定向时并不如同单个请求一样马上收包,而是将其再次加入请求队列执行defer等待收包
         * 待队列中所有原有并发请求第一次收包完毕后,再一同执行重定向收包,
         * 否则并发请求会由于重定向退化为队列请求,可以自行测试验证
         *
         * Notice! `withInQueue` is a concurrent redirection optimization
         * The principle is that instead of receiving a packet as soon as a single request is,
         * it is added to the request queue again and delayed to wait for the packet to recv.
         * After all the original concurrent requests in the queue for the first time are recved, the redirect requests recv again.
         * Otherwise, the concurrent request can be degraded to a queue request due to redirection, you can be tested and verified.
         */
        parent::enqueue($request->withInQueue(true));
    }

    public function getMaxConcurrency(): int
    {
        return $this->max_concurrency;
    }

    public function withMaxConcurrency(int $num = -1): self
    {
        $this->max_concurrency = $num;

        return $this;
    }

    /**
     * @return ResponseMap|Response[]
     */
    public function recv(): ResponseMap
    {
        $start_time = microtime(true);
        $res_map = new ResponseMap(); //Result-set
        $index = 0;

        // FIXME: 并发模式使用的是defer机制, 这一机制可以节省协程的创建, 但当重定向发生时它无法完美地并发, 并且在执行时会和use_pool上限产生冲突, 导致死锁, 所以需要将其改成channel调度的模式
        $max_co = $this->getMaxConcurrency();
        if ($max_co > 0 && $max_co < $this->count()) {
            if (!isset($this->concurrency_pool) || !$this->concurrency_pool->isEmpty()) {
                $this->concurrency_pool = new SplQueue();
            }
            while (!$this->isEmpty()) {
                $current_co = 0;
                //de-queue from the total pool and en-queue to the controllable pool
                while (!$this->isEmpty() && $max_co > $current_co++) {
                    /** @var $req Request */
                    $req = $this->dequeue();
                    $req->withSpecialMark($index++, 'requestQueueIndex');
                    if (!$req->isWaiting()) {
                        $req->exec();
                    } else {
                        throw new InvalidArgumentException("The waiting request is forbidden when using the max concurrency control!");
                    }
                    $this->concurrency_pool->enqueue($req);
                }
                while (!$this->concurrency_pool->isEmpty()) {
                    $req = $this->concurrency_pool->dequeue();
                    /** @var $req Request */
                    $res = $req->recv();
                    if ($res instanceof Request) {
                        $this->concurrency_pool->enqueue($res);
                    } else {
                        //response create
                        $res_map[$res->getSpecialMark('requestQueueIndex')] = $res;
                        if (($name = $req->getName()) && !isset($res_map[$name])) {
                            $res_map[$name] = &$res;
                        }
                    }
                }
                /** callback */
                $is_finished = false;
                $ret = $this->callInterceptor('after_concurrency', $res_map, $is_finished, $this);
                if ($ret !== null) {
                    return $ret;
                }
            }
        } else {
            /**@var $req Request */
            foreach ($this as $index => $req) {
                $req->withSpecialMark($index, 'requestQueueIndex')->exec();
            }
            $req = null;
            while (!$this->isEmpty()) {
                $req = $this->dequeue();
                /** @var $req Request */
                $res = $req->recv();
                if ($res instanceof Request) {
                    $this->enqueue($res);
                } else {
                    //response create
                    $res_map[$res->getSpecialMark('requestQueueIndex')] = $res;
                    if (($name = $req->getName()) && !isset($res_map[$name])) {
                        $res_map[$name] = &$res;
                    }
                    // clear mark
                    $req->withInQueue(false);
                }
            }
        }
        $res_map->time = microtime(true) - $start_time;

        /** callback */
        $is_finished = true;
        $ret = $this->callInterceptor('after_concurrency', $res_map, $is_finished, $this);
        if ($ret !== null) {
            return $ret;
        }

        return $res_map;
    }

}

<?php
/**
 * Author: Twosee <twose@qq.com>
 * Date: 2018/7/9 上午12:51
 */

/** =========== Process Manager ========== **/
class ProcessManager
{
    /**
     * @var swoole_atomic
     */
    protected $atomic;
    protected $alone = false;

    public $parentFunc;
    public $childFunc;
    public $async = false;

    protected $childPid;

    protected $parentFirst = false;

    function __construct()
    {
        $this->atomic = new swoole_atomic(0);
    }

    function setParent(callable $func)
    {
        $this->parentFunc = $func;
    }

    function parentFirst()
    {
        $this->parentFirst = true;
    }

    function childFirst()
    {
        $this->parentFirst = false;
    }

    function setChild(callable $func)
    {
        $this->childFunc = $func;
    }

    // wait for information
    function wait()
    {
        $this->atomic->wait();
    }

    // resume the waiting process
    function wakeup()
    {
        $this->atomic->wakeup();
    }

    function runParentFunc($pid = 0)
    {
        return call_user_func($this->parentFunc, $pid);
    }

    function runChildFunc()
    {
        return call_user_func($this->childFunc);
    }

    function fork($func)
    {
        $pid = pcntl_fork();
        if ($pid > 0) {
            return $pid;
        } elseif ($pid < 0) {
            return false;
        } else {
            call_user_func($func);
            exit;
        }
    }

    /**
     * kill child process
     */
    function kill()
    {
        if (!$this->alone) {
            swoole_process::kill($this->childPid);
        }
    }

    function run()
    {
        global $argv, $argc;
        if ($argc > 1) {
            if ($argv[1] == 'child') {
                $this->alone = true;
                return $this->runChildFunc();
            } elseif ($argv[1] == 'parent') {
                $this->alone = true;
                return $this->runParentFunc();
            }
        }
        $pid = pcntl_fork();
        if ($this->parentFirst) {
            $this->atomic->set(0);
        }
        if ($pid < 0) {
            echo "ERROR\n";
            exit;
        } //子进程
        elseif ($pid === 0) {
            //等待父进程
            if ($this->parentFirst) {
                $this->wait();
            }
            $this->runChildFunc();
            exit;
        } //父进程
        else {
            $this->childPid = $pid;
            //子进程优先运行，父进程进入等待状态
            if (!$this->parentFirst) {
                $this->wait();
            }
            $this->runParentFunc($pid);
            if ($this->async) {
                swoole_event::wait();
            }
            pcntl_waitpid($pid, $status);
        }
        return true;
    }
}

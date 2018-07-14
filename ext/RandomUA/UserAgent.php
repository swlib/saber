<?php
/**
 * Created by PhpStorm.
 * User: blcat <bl_cat@163.com>
 * Date: 2018/7/14
 * Time: 22:38
 */

namespace Swlib\saber;

class UserAgent
{
    /**
     * Random  User-Agent
     *
     * @return string
     */
    static public function getUserAgent():string
    {
        $filename = "user-agents.txt";
        if(file_exists($filename)){
            swoole_async_readfile($filename, function ($filename, $content){
                return self::afterget($content);
            });
        }else {
            $uri = "https://raw.githubusercontent.com/sqlmapproject/sqlmap/master/txt/user-agents.txt";
            $UA = SaberGM::get($uri)->body;
            swoole_async_writefile($filename, $UA);
            return self::afterget($UA);
        }
    }

    /**
     * @param string $UA 
     *
     * @return string
     */
    public function afterget(string $UA):string
    {
        $ua_arr = explode(PHP_EOL, $UA);
        // 随机获取一行作为ua
        $ua = $ua_arr[mt_rand(0,count($ua_arr))];
        while (preg_match('[# ]', $ua)){
            $ua = $ua_arr[mt_rand(0,count($ua_arr))];
        }
        return $ua;
    }
}
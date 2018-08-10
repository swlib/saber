<?php
/**
 * Author: Twosee <twose@qq.com>
 * Date: 2018/6/27 下午1:09
 */

use Swlib\Http\BufferStream;
use Swlib\Http\Exception\HttpExceptionMask;
use Swlib\Saber;
use Swlib\SaberGM;

$http = new swoole_http_server('0.0.0.0', 9999);

$http->set([
    'worker_num' => 1,
    'log_level' => SWOOLE_LOG_INFO,
    'trace_flags' => 0
]);

/**@var $saber Saber */
$http->on('workerStart', function () use (&$saber) {
    require_once __DIR__ . '/../vendor/autoload.php';
    // initialization
    SaberGM::exceptionReport(HttpExceptionMask::E_NONE);
    $saber = Saber::create([
        'base_uri' => 'https://news-at.zhihu.com/api/4/'
    ]);
    echo "Server is running on http://127.0.0.1:9999\n";
});

$http->on('request', function (swoole_http_request $request, swoole_http_response $response) use (&$saber) {
    $response->header('content-type', 'text/html; charset=UTF-8');
    if ($request->server['request_uri'] === '/') {
        global $page_head, $page_end;
        $resBuffer = new BufferStream($page_head);
        // get latest news
        $news = $saber->get('news/latest')->getParsedJsonArray();
        $image_uri_list = $content_uri_list = [];
        foreach ($news['stories'] as $story) {
            $image_uri_list[] = $story['images'][0];
            $content_uri_list[] = "news/{$story['id']}";
        }
        // get all images data
        $images_data = $saber->list(['uri' => $image_uri_list]); // multi requests
        foreach ($images_data as $index => $image_data) {
            $news['stories'][$index]['images'] = base64_encode($image_data->getBody());
        }
        // get all stories' content
        $contents_data = $saber->list(['uri' => $content_uri_list]);
        $resBuffer->write('<div class="zh-list">');
        foreach ($contents_data as $index => $content_data) {
            preg_match( // fetch question title
                '/<[h]2 class="question-title">(.*?)<\/h2>/',
                $content_body = ($news['stories'][$index]['content'] = $content_data->getParsedJsonArray())['body'],
                $match
            );
            if (empty($news['stories'][$index]['intro'] = $match[1] ?? '')) { // fetch intro
                preg_match('/<div class="content">\s+<p>(.*?)<\/p>/', $content_body, $match);
                $news['stories'][$index]['intro'] = $match[1] ?? '' ?: '';
            }
        }
        //output
        foreach ($news['stories'] as $story) {
            $resBuffer->write(<<<HTML
<div class="zh-row" onclick="window.open('{$story['content']['share_url']}')">
  <img class="zh-img" src="data:image/png;base64,{$story['images']}">
  <div class="zh-title">{$story['title']}</div>
  <div class="zh-intro">{$story['intro']}</div>
</div>
HTML
            );
        }
        $resBuffer->write('</div>');
        $response->end($resBuffer->write($page_end));
    } else {
        $response->status(404);
        $response->end();
    }
});

$page_head = <<<HTML
<html>
<head>
<title>知乎日报</title>
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" name="viewport">
<style>
body{
margin: 0;
}
.zh-nav{
margin: 0 auto;
background: #459DF5;
position: relative;
height: 44px;
line-height: 44px;
overflow: hidden;
z-index: 100;
text-align: center;
background: linear-gradient(#4da7ff,#42a0ff);
}
.zh-nav-title{
font-size: 20px;
color: #fff;
}
.zh-list{
margin: 4px;
}
.zh-row{
height: 110px;
padding: 10px;
margin-bottom: 10px;
border: 1px solid #e4e4e4;
border-radius: 10px;
box-shadow: 1px 1px 15px 0 #e6e6e6;
overflow: hidden;
text-overflow: ellipsis;
cursor: pointer;
}
.zh-img{
float: left;
max-width: 30%;
border-radius: 10px;
}
.zh-title{
position: relative;
left: 10px;
top: -5px;
padding: 10px;
font-weight: 500;
}
.zh-intro{
height: 35px;
position: relative;
left: 10px;
top: -10px;
padding-right: 10px;
font-size: 13px;
overflow: hidden;
}
</style>
</head>
<body>
<div class="zh-nav"><span class="zh-nav-title">知乎日报</span></div> 
HTML;
$page_end = <<<HTML
</body>
</html>
HTML;

$http->start();

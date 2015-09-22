<?php
require_once('./vendor/autoload.php');
use Abraham\TwitterOAuth\TwitterOAuth;

require_once('./config/twitter_config.php');
require_once('./config/config.php');

require_once('./class/item.php');
require_once('./class/lab.php');
require_once('./helper/log_helper.php');

$pre_data = read_data();
$data = get_newdata();

$labs = create_labs($pre_data, $data);

if (!empty($labs)) {
    $post_texts = create_text($labs);
    post_tweets($post_texts);
    save_data($data);
}

function get_newdata() {
    return json_decode(file_get_contents(API_URL));
}

function create_labs($data, $pre_data) {
    // HACK: to zip loop
    $labs = array();
    foreach($data as $lab_name => $n) {
        $lab = new Lab($lab_name, $n, $pre_data->{$lab_name});
        if ($lab->is_updated()) {
            $labs[] = $lab;
        }
    }
    return $labs;
}

function create_text($name, $lab_name, $num, $max, $prev_lab) {
    // TODO: 全体的にconstants 化
    echo $prev_lab . PHP_EOL;
    if (in_array($lab_name, array('学科外(系列等)', '(未定)'))) {
        return <<<EOF
{$lab_name} 登録者が増えました
{$num}名

EOF;
    }
    $fill = $max < $num ? $max : $num;
    $over = $max < $num ? $num - $max : 0;
    $emp  = $max - $fill;
    $name_suffix = '研';
    $graph = str_repeat('■', $fill) . str_repeat('□', $emp) . str_repeat('◆', $over);
    $text = '';
    if (isset($prev_lab)) {
        $text .= "{$name}さん が {$prev_lab}{$name_suffix} に希望を失いました\n";
    }
    $text .= <<<TEXT
{$name}さん が {$lab_name}{$name_suffix} に希望しました
【{$lab_name}{$name_suffix}】
{$graph}
$num/$max

TEXT;
    return $text;
}

function post_tweets($post_texts) {
    foreach($post_texts as $i => $text) {
        if (DEBUG) {
            echo $text;
            continue;
        }
        $to = new TwistOAuth($userdata->twitter_consumer_key, $userdata->twitter_consumer_key_secret, $userdata-
        $url = 'statuses/update';
        $param = array(
            'status' => $text,
        );
        try {
            $res = $to->post($url, $param);
        } catch (TwistException $e) {
            echo 'post deplicate' . PHP_EOL;
        }
        echo '--';
        var_dump($res);
    }
}

<?php
require_once('./vendor/autoload.php');
use Abraham\TwitterOAuth\TwitterOAuth;

require_once('./config/twitter_config.php');
require_once('./config/config.php');

require_once('./class/item.php');
require_once('./helper/log_helper.php');

$data = read_data();
$last_id = $data->last->{'0'};
$items = get_rss_items($data);
# $labs = get_assign();

$posts = get_posts($items, $last_id);
if (DEBUG) {
    var_dump($posts);
}
post_tweets($posts, $userdata);
var_dump($posts);
save_data($data);

// methods
function get_rss_items(&$data) {
    $url = 'https://' . ID . ':' . PASS . '@www.mlab.im.dendai.ac.jp/bthesis/bachelor/rss.xml';
    $rss = simplexml_load_file($url);
    $items = array();
    $i = 0;
    foreach ($rss->item as $item) {
        $rdf = $item->attributes('http://www.w3.org/1999/02/22-rdf-syntax-ns#');
        $items[] = new Itemobj($rdf->about, $item->title, $rdf->about[0]);
        if ($i++ == 0) {
            $data->last = $rdf->about[0];
        }
    }

    return array_reverse($items);
}


function post_tweets($posts, $userdata) {
    foreach($posts as $i => $text) {
        if ($i == 5) {
            break;
        }
        if (DEBUG) {
            echo $text;
            continue;
        }
        $to = new TwistOAuth($userdata->twitter_consumer_key, $userdata->twitter_consumer_key_secret, $userdata->twitter_access_token, $userdata->twitter_access_token_secret);
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

function get_posts($items, $last_id) {
    $texts = array();
    $labs = array();
    $is_new = FALSE;
    foreach ($items as $item) {
        $prev_lab = NULL;
        $uid = $item->uid;
        $lab_name = $item->lab_name;
        if (! isset($labs[$lab_name])) {
            $labs[$lab_name] = array();
        }
        // HACK: havy roop
        foreach ($labs as $lab) {
            if (! in_array($uid, $lab)) {
                $labs[$lab_name] = array_diff($labs[$lab_name], array($uid));
                $prev_lab = $lab_name;
                break;
            }
        }
        $labs[$lab_name][] = $uid;
        if ($is_new) {
            $subs = explode(',', '山田,柿崎,森本,森谷');
            $max = in_array($lab_name, $subs) ? 2 : 12;
            $texts[] = create_text($lab_name, count($labs[$lab_name]), $max);
        }
        if (DEBUG) {
            echo $item->rdf_id . ':' . $last_id . PHP_EOL;
        }

        if ($item->rdf_id == $last_id) {
            $is_new = TRUE;
        }
    }
    return $texts;
}

function create_text($name, $num, $max) {
    // TODO: 全体的にconstants 化
    if (in_array($name, array('学科外(系列等)', '(未定)'))) {
        return <<<EOF
{$name} 登録者が増えました
{$num}名

EOF;
    }
    $fill = $max < $num ? $max : $num;
    $over = $max < $num ? $num - $max : 0;
    $emp  = $max - $fill;
    $name_suffix = '研';
    $graph = str_repeat('■', $fill) . str_repeat('□', $emp) . str_repeat('◆', $over);
    $text = <<<TEXT
【2015年度 13FI生】
{$name}{$name_suffix} 希望が増えました
{$graph}
$num/$max

TEXT;
    return $text;
}



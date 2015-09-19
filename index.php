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
var_dump($posts);
post_tweets($posts, $userdata);
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
        # skip no move
        if (in_array($uid, $labs[$lab_name])) {
            continue;
        }
        // HACK: havy roop
        foreach ($labs as $lname => $lab) {
            if (in_array($uid, $lab)) {
                $labs[$lname] = array_diff($labs[$lname], array($uid));
                $prev_lab = $lname;
                break;
            }
        }
        $labs[$lab_name][] = $uid;
        if ($is_new) {
            $subs = explode(',', '山田,柿崎,森本,森谷');
            $max = in_array($lab_name, $subs) ? 2 : 12;
            $texts[] = create_text($item->get_secret_name(), $lab_name, count($labs[$lab_name]), $max, $prev_lab);
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
{$name}さん が {$lab_name}{$name_suffix} に希望を見い出しました
【{$lab_name}{$name_suffix}】
{$graph}
$num/$max

TEXT;
    return $text;
}



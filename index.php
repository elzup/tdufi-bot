<?php
require_once('./vendor/autoload.php');
use Abraham\TwitterOAuth\TwitterOAuth;

require_once('./config/twitter_config.php');
require_once('./config/config.php');

require_once('./class/lab.php');
require_once('./helper/log_helper.php');

$pre_data = read_data();
$data = get_newdata();

$labs = create_labs($data, $pre_data);

if (!empty($labs)) {
    $post_texts = get_post_tweets($labs);
    post_tweets($post_texts, $userdata);
    save_data($data);
}

function get_post_tweets($labs) {
    $post_texts = array();
    foreach ($labs as $lab) {
        $post_texts[] = $lab->get_tweet_text();
    }
    return $post_texts;
}

function get_newdata() {
    return json_decode(file_get_contents(API_URL));
}

function create_labs($data, $pre_data) {
    // HACK: to zip loop
    $labs = array();
    foreach($data as $lab_name => $n) {
        $lab = new Lab($lab_name, $n, $pre_data->{$lab_name});
        if ($lab->is_include() and $lab->is_main()) {
            $labs[] = $lab;
        }
    }
    return $labs;
}

function post_tweets($post_texts, $userdata) {
    foreach($post_texts as $i => $text) {
        echo 'POST:' . PHP_EOL;
        echo $text . PHP_EOL;
        if (DEBUG) {
            continue;
        }
        $to = new TwistOAuth(
            $userdata->twitter_consumer_key,
            $userdata->twitter_consumer_key_secret,
            $userdata->twitter_access_token,
            $userdata->twitter_access_token_secret
        );
        $url = 'statuses/update';
        $param = array(
            'status' => $text,
        );
        try {
            $res = $to->post($url, $param);
        } catch (TwistException $e) {
            var_dump($e);
        }
    }
}

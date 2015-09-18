<?php
require_once('./vendor/autoload.php');

require_once('./config/twitter_config.php');
require_once('./config/config.php');

$data = read_data();
$last_id = $data->last->{'0'};
$items = get_rss_items($data);
save_data($data);
$items = array_reverse($items);
# $labs = get_assign();

$posts = get_posts($items, $last_id);
var_dump($posts);
exit();
post_tweets($posts, $userdata);


// class
class Itemobj { 
    public $lab_str;
    public $univ_id;
    public $rdf_id;

    public function __construct($about, $title, $rdf_id) {
        $this->rdf_id = $rdf_id;
        if (strpos($title, '未定') != FALSE) {
            $this->lab_str = '(未定)';
            return;
        }
        if (strpos($title, '学科外') != FALSE) {
            $this->lab_str = '学科外(系列等)';
            return;
        }
        preg_match('#\((?<uid>.*?)\).*「(?<name>.*?)研究室 \((?<lid>.)\)」#u', $title, $m);
//        $this->lab_id = $m['lid'];
        $this->lab_str = $m['name'];
        $this->univ_id = $m['uid'];
    }
}

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
    return $items;
}

# @Depected
function get_assign() {
    $url = 'http://www.mlab.im.dendai.ac.jp/bthesis2015/StudentDeploy.jsp?displayOrder=2';
    $data = array(
        "id" => ID,
        "code" => PASS,
        "func" => "authByRadius"
    );
    $data = http_build_query($data, "", "&");
    //header
    $header = array(
        "Content-Type: application/x-www-form-urlencoded",
        "Content-Length: ".strlen($data)
    );
    $context = array(
        "http" => array(
            "method"  => "POST",
            "header"  => implode("\r\n", $header),
            "content" => $data
        )
    );
    $f = file_get_contents($url, false, stream_context_create($context));
    $html = str_get_html($f);
    $labs = array();
    $names_text = '星野 絹川 佐々木 小山 矢島 齊藤 小坂 中島 高橋 鉄谷 川澄 増田 岩井 竜田 山田 柿崎 森本 森谷 学科外(系列等) (未定)';
    foreach (explode(' ', $names_text) as $name) {
        $labs[$name] = 0;
    }
    foreach ($html->find('tr') as $tr) {
        if ($tr->find('th')) {
            continue;
        }
        $name = $tr->find('td', 2)->innertext;
        $labs[$name]++;
    }
    return $labs;
}

/**
 * 既読フィードログファイルの読み取り
 */
function read_data() {
    // TODO: 空ファイル例外
    $handle = fopen(FILE_NAME, 'r');
    $data = json_decode(fread($handle, filesize(FILE_NAME)));
    fclose($handle);
    return $data;
}

/**
 * 既読フィードログファイルの更新
 */
function save_data($data) {
    $handle = fopen(FILE_NAME, 'w');
    fwrite($handle,json_encode($data));
    fclose($handle);
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
        $connection = new TwitterOAuth($userdata->twitter_consumer_key, $userdata->twitter_consumer_key_secret, $userdata->twitter_access_token, $userdata->twitter_access_token_secret);
        $url = 'statuses/update';
        $param = array(
            'status' => $text,
        );
        $connection->post($url, $param);
    }
}

function get_posts($items, $last_id) {
    $texts = array();
    $labs = array();
    $is_new = FALSE;
    foreach ($items as $item) {
        if (! isset($labs[$item->lab_str])) {
            $labs[$item->lab_str] = array();
        }
        // HACK: havy roop
        foreach ($labs as $lab) {
            if (! in_array($item->univ_id, $lab)) {
                $labs[$item->lab_str] = array_diff($labs[$item->lab_str], array($item->univ_id));
            }
        }
        $labs[$item->lab_str][] = $item->univ_id;
        if ($is_new) {
            $subs = explode(',', '山田,柿崎,森本,森谷');
            $max = in_array($item->lab_str, $subs) ? 2 : 12;
            $texts[] = create_text($item->lab_str, count($labs[$item->lab_str]), $max);
        }
        if (DEBUG) {
            echo $item->rdf_id . ':' . $last_id . PHP_EOL;
        }

        if ($item->rdf_id == $last_id) {
            echo '--';
            echo $item->rdf_id . ':' . $last_id . PHP_EOL;
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


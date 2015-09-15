<?php
require_once('./lib/simple_html_dom.php');
require_once('./lib/twitteroauth.php');
require_once('./lib/userdata.php');

$data = read_data();
$items = get_rss_items($data);
if ($items) {
    save_data($data);
}
$items = array_reverse($items);
$labs = get_assign();
$posts = get_posts($labs, $items);
post_tweets($posts, $userdata);

// class
class Itemobj { 
//    public $about;
//    public $lab_id;
//    public $lab_str;
    public $univ_id;

    public function __construct($about, $title) {
//        $this->about = $about;
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
//        $this->univ_id = $m['uid'];
    }
}

// methods
function get_rss_items(&$data) {
    list($key,$last_id) = each($data->last);
    if (DEBUG) {
        $last_id = 'hoge';
    }
    $url = 'https://' . ID . ':' . PASS . '@www.mlab.im.dendai.ac.jp/bthesis/bachelor/rss.xml';
    $rss = simplexml_load_file($url);
    $items = array();
    $i = 0;
    foreach ($rss->item as $item) {
        $rdf = $item->attributes('http://www.w3.org/1999/02/22-rdf-syntax-ns#');
        if ($rdf->about[0] == $last_id) {
            break;
        }
        $items[] = new Itemobj($rdf->about, $item->title);
        if ($i++ == 0) {
            $data->last = $rdf->about;
        }
    }
    return $items;
}


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

function get_posts($labs, $items) {
    $texts = array();
    foreach ($items as $s) {
        $subs = explode(',', '山田,柿崎,森本,森谷');
        $max = in_array($s->lab_str, $subs) ? 2 : 12;
        if (!isset($labs[$s->lab_str])) {
            continue;
        }
        $texts[] = create_text($s->lab_str, $labs[$s->lab_str], $max);
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
{$name}{$name_suffix} 希望が増えました
{$graph}
$num/$max

TEXT;
    return $text;
}


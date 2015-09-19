<?php

class Itemobj {
    public $lab_name;
    public $uid;
    public $rdf_id;
    public static $names;

    public function __construct($about, $title, $rdf_id) {
        $this->rdf_id = $rdf_id;
        if (strpos($title, '未定') != FALSE) {
            $this->lab_name = '(未定)';
            return;
        }
        if (strpos($title, '学科外') != FALSE) {
            $this->lab_name = '学科外(系列等)';
            return;
        }
        preg_match('#\((?<uid>.*?)\).*「(?<name>.*?)研究室 \((?<lid>.)\)」#u', $title, $m);
//        $this->lab_id = $m['lid'];
        $this->lab_name = $m['name'];
        $this->uid = $m['uid'];
    }

    public function get_secret_name() {
        $_uid = (int) substr($this->uid, 4, 3);
        if (isset(Itemobj::$names[$_uid])) {
            return Itemobj::$names[$_uid];
        }
        return "アンドレ";

    }
}
$f = file_get_contents(dirname(__FILE__) . '/../data/words.txt');
Itemobj::$names = explode("\n", trim($f));

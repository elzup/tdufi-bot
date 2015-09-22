<?php

class Lab {
    public $lab_name;
    public static $lab_name_prefix = '県';
    public $pre_num;
    public $num;

    public function __construct($lab_name, $num, $pre_num) {
        $this->lab_name = $lab_name;
        $this->num = $num;
        $this->pre_num = $pre_num;
    }

    public function is_updated() {
        return $this->num != $this->pre_num;
    }

    public function is_include() {
        return $this->num > $this->pre_num;
    }

    public function get_tweet_text() {
        $text = $this->get_update_text();
        if ($this->is_main()) {
            $text .= $this->get_rate_graph_text();
        }
        return $text;
    }

    public function is_main() {
        return ! in_array($this->lab_name, array('学科外(系列等)', '(未定)'));
    }

    public function get_update_text() {
        return "{$this->lab_name}星 希望者が増えました\n {$this->num}名 / {$this->get_member_limit()}名\n";
    }


    public function get_member_limit() {
        $subs = explode(',', '山田,柿崎,森本,森谷');
        return in_array($this->lab_name, $subs) ? 2 : 12;
    }

    public function get_rate_graph_text() {
        $num = $this->num;
        $max = $this->get_member_limit();
        $fill = $max < $num ? $max : $num;
        $over = $max < $num ? $num - $max : 0;
        $emp  = $max - $fill;
        return str_repeat('■', $fill) . str_repeat('□', $emp) . str_repeat('◆', $over);
    }
}


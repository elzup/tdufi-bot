<?php

class Lab {
    public $lab_name;
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
}

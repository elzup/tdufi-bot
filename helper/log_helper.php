<?php
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


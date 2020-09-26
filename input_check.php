<?php
//nullバイトは一切許可しない
//sorce https://qiita.com/tabo_purify/items/d7a67709f54865df891e
function sanitizer($arr) {
    if (is_array($arr)) {
        return array_map('sanitizer', $arr);    
    }
    return str_replace("\0", "", $arr);
}
$_GET = sanitizer($_GET);
$_POST = sanitizer($_POST);
$_COOKIE = sanitizer($_COOKIE);

//-----入力値に配列を寄越してきたときはダメ------------------
function check1($key) {
    if (isset($_POST[$key]) && is_string($_POST[$key])) {
        return $_POST[$key];
    } else {
        return null;
    }
}
//スペースを死なせる
function killSpaceCheck($input){
    return preg_replace( '/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $input) === "" ? null : $input;
}

//null文字 or 馬鹿には見えない文字しか入力されていないときは受け付けない．
function checkInput($key){
    return killSpaceCheck(check1($key));
}

//-----表示するとき，エスケープ処理
function h($string) {
    if (is_array($string)) {
        return array_map("h", $string);
    } else {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
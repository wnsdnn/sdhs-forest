<?php

spl_autoload_register(function($f) {
    require_once("../{$f}.php");
});


function script($s) {
    echo "<script>$s</script>";
};

function alert($t = "") {
    !empty($t) && script("alert('$t');");
};

function view($page, $data = []) {
    extract($data);

    require_once("../src/views/template/header.php");
    require_once("../src/views/{$page}.php");
    require_once("../src/views/template/footer.php");
};

function move($tg, $t = "") {
    alert($t);
    script("location.replace('$tg');");
    exit;
};

function back($t = "") {
    alert($t);
    script("history.back();");
    exit;
};


function user()
{
    return isset($_SESSION["user"]) ? $_SESSION["user"] : false;
};


function loginChk()
{
    if(!user()) {
        move("/login", "로그인을 하신 뒤 이용해주세요");
    }
};

function getItem($tg, ...$names)
{
    return array_map(function($name) use ($tg) {
        return $tg[$name];
    }, $names);
};

function get(...$names) {
    return getItem($_GET, ...$names);
};

function post(...$names) {
    return getItem($_POST, ...$names);
};


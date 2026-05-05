<?php
date_default_timezone_set('Asia/Manila');
// HRMSV3 Configuration
define("DB_HOST","127.0.0.1"); define("DB_PORT","3306"); define("DB_NAME","hrms_db");
define("DB_USER","root");     define("DB_PASS","");       define("DB_CHARSET","utf8mb4");
define("APP_NAME","Staffora HRMS"); define("APP_VERSION","3.0"); define("BASE_URL","/HRMSV3");
define("UPLOAD_PATH",__DIR__."/uploads/"); define("UPLOAD_URL",BASE_URL."/uploads/");

ini_set("session.cookie_httponly",1); ini_set("session.use_strict_mode",1);
session_name("HRMSV3_SESSION");
if(session_status()===PHP_SESSION_NONE) session_start();

if(!function_exists("e")){
    function e(?string $s):string{ return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"); }
}
if(!function_exists("url")){
    function url(string $page,array $params=[]):string{
        return BASE_URL."/index.php?".http_build_query(array_merge(["page"=>$page],$params));
    }
}
if(!function_exists("redirect")){
    function redirect(string $page,array $params=[]):void{ header("Location: ".url($page,$params)); exit; }
}

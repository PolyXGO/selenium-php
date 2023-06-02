<?php

require_once('vendor/autoload.php');

use Facebook\WebDriver\Remote\RemoteWebDriver;

//TODO: ================== Note ==================
//1. git clone https://github.com/php-webdriver/php-webdriver
//2. Update composer => composer update
//3. Cài đặt Java JDK + thiết lập biến môi trường (nếu chưa cài).
//4. Tải Selenium Server jar. Giải nén vào thư mục E hoặc C.
//5. Mở CMD chạy server qua lệnh: java -jar "E:\selenium-server-4.9.1.jar" standalone --port 4444
// => Không thành công cần thử lại 2+ lần, thường lần đầu có thể không start thành công.
// => Thành công lấy link gán cho biến $host bên dưới để thiết lập server test;
//6. Viết code testcase cần thực hiện.

//TODO: cần tham khảo tài liệu DOM của Selenium hỗ trợ để tương tác các phần tử DOM HTML trước khi viết test.
//TODO: ================== END Note ==================

require_once('includes/config.php');//Config;
require_once('includes/helper.php');//Helper functions
require_once('includes/common.php');//Common functions;

//TODO: ================== Test Login ==================
$list_site_testing=[
    [
        'domain'=>'http://localhost/polyxgo',
        'user_name'=>'admin',
        'password'=>'1'
    ],
];

$host = host_server;
$capabilities = Facebook\WebDriver\Remote\DesiredCapabilities::chrome();

foreach ($list_site_testing as $current_site_infor) {
    $driver = RemoteWebDriver::create($host, $capabilities);
    test_login($driver,$current_site_infor);
    $driver->quit();
}

//TODO: ================== END Test Login ==================

#region functions
function test_login($driver,$current_site_infor=null){
    if(empty($current_site_infor)) return '';

    $object_log=new stdClass;
    $object_log->type='Login';//Login;
    $object_log->domain=$current_site_infor['domain'];
    
    $date_start=get_date_time_client();
    $object_log->test_at=$date_start;

    //TODO: Login
    $start_time_login = microtime(true);
    $is_logged=is_logged($driver,$current_site_infor);
    $object_log->login_status=$is_logged;
    $end_time_login = microtime(true);
    $object_log->login_time=round($end_time_login - $start_time_login,4);

    $log_temp="====== Thời gian: ".$date_start." ======<br/>";
    $log_temp.= "Login ".$object_log->domain.(($is_logged==true)?' ':' không ')."thành công<br/>";
    $log_temp.= "Thời gian thực thi: " .$object_log->login_time . " giây<br/>";
    $log_temp.= "===========================================<br/>";
    echo $log_temp;

    //Ghi thông tin vào file logs/logs.txt
    $log_content=write_logs(json_encode($object_log),'logs_login');
    echo $log_content;
}
#endregion functions
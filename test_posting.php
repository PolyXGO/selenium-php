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

//TODO: ================== Test Posting ==================
$list_site_testing=[
    [
        'domain'=>'http://localhost/polyxgo',
        'user_name'=>'admin',
        'password'=>'1'
    ],
];

$host = host_server;
$capabilities = Facebook\WebDriver\Remote\DesiredCapabilities::chrome();

//TODO: Loop $list_site_testing => post draft;
foreach ($list_site_testing as $current_site_infor) {
    $driver = RemoteWebDriver::create($host, $capabilities);
    test_posting($driver,$current_site_infor);
    $driver->quit();//Exit browser;
}

//TODO: ================== END Test Posting ==================

#region functions
function test_posting($driver,$current_site_infor=null){
    if(empty($current_site_infor)) return '';//Không xử lý nếu không có thông tin site;

    $object_log=new stdClass;
    $object_log->type='Posting';//Đăng bài;
    $object_log->domain=$current_site_infor['domain'];

    $start_time = microtime(true);
    $start_memory = memory_get_peak_usage();
    $date_start=get_date_time_client();
    $object_log->test_at=$date_start;

    //TODO: Login
    $start_time_login = microtime(true);
    $is_logged=is_logged($driver,$current_site_infor);
    $object_log->login_status=$is_logged;
    $end_time_login = microtime(true);
    $object_log->login_time=round($end_time_login - $start_time_login,4);

    if(!$is_logged) return '';//Không xử lý nếu login site hiện tại không thành công;

    //Posting: Test tạo bài viết mới + thêm hình ảnh => đăng bài chế độ nháp.
    //Xử lý upload hình ảnh trước qua post-media => xử lý tạo + chèn bài viết => lưu nháp.

    //TODO: upload file;
    $file_path_test=root_path_file_test_upload.'1.jpg';
    $start_time_upload = microtime(true);
    $image_url_path=test_upload_file_from_media($driver,$file_path_test,$current_site_infor);
    $end_time_upload = microtime(true);

    $object_log->upload_time=round($end_time_upload - $start_time_upload, 4);

    if(empty($image_url_path)) return;//'Upload không thành công tập tin => kết thúc test';

    //TODO: Đăng bài.
    $obj=new stdClass;
    $obj->title="Tiêu đề bài viết test ".get_date_time_client();
    $obj->description="Nội dung bài viết test ".get_date_time_client();
    $obj->media=$image_url_path;

    $start_time_post = microtime(true);
    $is_posted=post_article($driver,$current_site_infor,$obj);
    $end_time_post = microtime(true);
    $object_log->post_time=round($end_time_post - $start_time_post, 4);

    $end_time = microtime(true);
    $end_memory = memory_get_peak_usage();

    //Tính toán thời gian thực thi
    $object_log->execution_time = round($end_time - $start_time,4);//Second;
    //Tính toán bộ nhớ xử lý
    $object_log->memory_usage = round(($end_memory - $start_memory)/1024,2);//KB

    //Kết quả thông tin thực thi;
    $log_temp="====== Thời gian: ".$object_log->test_at." ======<br/>";
    $log_temp.= "Đăng bài nháp ".$object_log->domain.($is_posted?' ':' không ')."thành công<br/>";
    $log_temp.= "Thời gian thực thi: " . $object_log->execution_time . " giây<br/>";
    $log_temp.= "Dung lượng bộ nhớ sử dụng: " . $object_log->memory_usage . " KB<br/>";
    $log_temp.= "===========================================<br/>";
    echo $log_temp;

    //Ghi logs/logs_posting.txt
    $log_content=write_logs(json_encode($object_log),'logs_posting');

    //Hiển thị thông tin logs
    echo $log_content;
}

#endregion functions
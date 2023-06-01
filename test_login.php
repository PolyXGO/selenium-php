<?php

require_once('vendor/autoload.php');

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

//TODO: ================== Note ==================
//1. git clone https://github.com/php-webdriver/php-webdriver
//2. Update composer => composer update
//3. Cài đặt Java JDK + thiết lập biến môi trường (nếu chưa cài).
//4. Tải Selenium Server jar. Giải nén vào thư mục E hoặc C. Sau đó cd chuyển tới root thư mục vừa giải nén.
//5. Mở CMD chạy server qua lệnh: java -jar "E:\selenium-server-4.9.1.jar" standalone --port 4444
// => Không thành công cần thử lại 2+ lần, thường lần đầu có thể không start thành công.
// => Thành công lấy link gán cho biến $host bên dưới để thiết lập server test;
//6. Viết code testcase cần thực hiện.

//TODO: cần tham khảo tài liệu DOM của Selenium hỗ trợ để tương tác các phần tử DOM HTML trước khi viết test.
//TODO: ================== END Note ==================
$list_site_testing=[
    [
        'domain'=>'http://localhost/polyxgo',
        'user_name'=>'admin',
        'password'=>'1'
    ],
    [
        'domain'=>'http://localhost/polyxgo',
        'user_name'=>'admin',
        'password'=>'1'
    ]
];

//TODO: access server test
//IP server sẽ chạy chromedriver testing từ Java Machine. IP này sinh ra mỗi lần khởi chạy CMD thành công qua lệnh.
//java -jar "E:\selenium-server-4.9.1.jar" standalone --port 4444
$host = 'http://192.168.1.107:4444/';
$capabilities = Facebook\WebDriver\Remote\DesiredCapabilities::chrome();

foreach ($list_site_testing as $current_site_infor) {
    $driver = RemoteWebDriver::create($host, $capabilities);
    test_login($driver,$current_site_infor);
    $driver->quit();//Thoát khỏi chromedriver
}
echo "End test<br/>";

#region Testing
function test_posting($driver,$current_site_infor=null){
    if(empty($current_site_infor)) return '';

    $current_domain=$current_site_infor['domain'];

    //TODO: 2. Test tạo bài viết mới + thêm hình ảnh => đăng bài chế độ nháp.
    //Truy cập menu Viết bài mới.
    $driver->get($current_domain.'/wp-admin/post-new.php');

    //Tại trang viết bài mới tìm vị trí nhập tiêu đề, nội dung bài viết
    $post_title = $driver->findElement(WebDriverBy::name('post_title'));

    //Auto nhập nội dung cho title
    $post_title->sendKeys('Tiêu đề bài viết test');

    //Blog không dùng tinymce có thể tìm kiếm phần tử nhập nội dung qua id sau:
    //$post_description_element = $driver->findElement(WebDriverBy::id('content'));

    //Dùng TinyMCE cần tìm kiếm id phần tử nội dung qua iframe.
    $iframe_content = $driver->findElement(WebDriverBy::id('content_ifr'));
    $driver->switchTo()->frame($iframe_content);
    $post_description_element = $driver->findElement(WebDriverBy::id('tinymce'));

    //Auto nhập nội dung cho description
    $post_description_element->sendKeys('Nội dung mô tả bài viết test');

    //Thêm hình ảnh
    $post_description_element->sendKeys('<p><img src="đường-dẫn-đến-hình-ảnh.jpg" alt="Hình ảnh"></p>');

    //Switch về lại default content (#iframe);
    $driver->switchTo()->defaultContent();

    //Save chế độ nháp bài viết
    $driver->findElement(WebDriverBy::id('save-action'))->click();
    // Chờ đến khi bài viết lưu thành công. Nhận diện qua việc xuất hiện id="message" báo tình trạng lưu.
    $driver->wait()->until(
        WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::id('message'))
    );
    $message = $driver->findElement(WebDriverBy::id('message'))->getText();
    //Đối với blog sử dụng ngôn ngữ khác cần check từ tương ứng với thông báo updated.
    if (strpos($message, 'Post updated.') !== false) {
        //Lưu bài viết thành công.
    } else {
        //Lưu bài viết không thành công.
    }
}
function test_login($driver,$current_site_infor=null){
    if(empty($current_site_infor)) return '';

    $current_domain=$current_site_infor['domain'];

    //TODO: 1. Step Test login page
    //==> Biến ghi nhận thời gian thực thi
    $start_time = microtime(true);
    $start_memory = memory_get_peak_usage();
    $date_start=get_date_time_client();

    //==> Truy cập url login blog, nên dùng path gốc chưa rewrite /wp-login.php
    $driver->get($current_domain.'/wp-login.php');

    //Tìm 2 phần tử liên quan tới input usernam mặc định của wordpress là name="log" => xác định vị trí auto nhập id.
    // name="pwd" xác định vị trí auto nhập mật khẩu.
    $username = $driver->findElement(WebDriverBy::name('log'));
    $password = $driver->findElement(WebDriverBy::name('pwd'));

    //Auto nhập thông tin mật khẩu login website $current_domain
    $username->sendKeys($current_site_infor['user_name']);
    $password->sendKeys($current_site_infor['password']);

    //Auto click submit login;
    $password->submit();

    //Đợi cho đến khi quá trình submit hoàn tất sử dụng wait()->until() chờ đến khi url chứa từ khóa "wp-admin" (login thành công vào trang quản trị gán giá trị cho $is_logged) hoặc quá thời gian thực thi server không phản hồi sẽ trả về false gán cho $is_logged "Login failed".
    $is_logged=true;//Mặc định login thành công ngược lại login = false nếu thất bại.
    $is_logged=$driver->wait()->until(
        WebDriverExpectedCondition::urlContains('wp-admin'),false
    );
    //Hoặc sử dụng Chờ trang kết quả có thẻ title là nội dung nhận diện trang cần kiểm tra qua ::titleContains();
    /*$driver->wait()->until(
        WebDriverExpectedCondition::titleContains('Dashboard')
    );*/

    //==> Ngừng ghi nhận thời gian thực thi quá trình login.
    $end_time = microtime(true);
    $end_memory = memory_get_peak_usage();

    //Tính toán thời gian thực thi
    $execution_time = $end_time - $start_time;
    //Tính toán bộ nhớ xử lý
    $memory_usage = $end_memory - $start_memory;

    //Kết quả thông tin thực thi quá trình login

    $log_temp="====== Thời gian: ".$date_start." ======\r\n";
    $log_temp.= "Login ".$current_domain.(($is_logged==true)?' ':' không')."thành công\r\n";
    $log_temp.= "Thời gian thực thi: " . round($execution_time, 4) . " giây\r\n";
    $log_temp.= "Dung lượng bộ nhớ sử dụng: " . round($memory_usage / 1024, 2) . " KB\r\n";
    $log_temp.= "===========================================\r\n";

    //Ghi thông tin vào file logs/logs.txt
    write_logs($log_temp);
}
#endregion Testing

#region Helper
function write_logs($log_content=''){
    $logDir='logs';
    $logFile = $logDir.'/logs.txt'; // Đường dẫn tới tập tin log.txt
    if (!file_exists($logDir)) {//Tạo thư mục logs nếu chưa tồn tại trên root path.
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $log_content . PHP_EOL, FILE_APPEND | LOCK_EX);
    // Hiển thị nội dung từ tập tin log.txt
     $logContent = file_get_contents($logFile);
     echo $logContent;
}
function get_date_time_client(){
    // Đặt múi giờ mặc định là GMT+0
    date_default_timezone_set('GMT');
    // Lấy thời gian hiện tại của máy chủ
    $serverTime = time();
    // Đặt múi giờ là GMT+7
    date_default_timezone_set('Asia/Bangkok');
    // Chuyển đổi thời gian hiện tại của máy chủ sang múi giờ GMT+7
    $clientTime = date('d/m/Y H:i:s', $serverTime);
    return $clientTime; // Hiển thị giờ hiện tại của máy khách
}
#endregion Helper
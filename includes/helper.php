<?php

#region Helper
function write_logs($log_content='',$file_name='temp'){
    $logDir=log_dir_name;
    $logFile = $logDir.'/'.$file_name.'.txt'; // Đường dẫn tới tập tin log $file_name.txt
    if (!file_exists($logDir)) {//Tạo thư mục logs nếu chưa tồn tại trên root path.
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $log_content . PHP_EOL, FILE_APPEND | LOCK_EX);
    // Hiển thị nội dung từ tập tin log.txt
     $logContent = file_get_contents($logFile);
     $logContent= str_replace("\r\n","<br/>",$logContent);
     return  $logContent;
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
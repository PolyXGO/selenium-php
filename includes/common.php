<?php
require_once('vendor/autoload.php');

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
function post_article($driver,$current_site_infor,$obj){
    //Tạo bài viết mới.
    $driver->get($current_site_infor['domain'].'/wp-admin/post-new.php');

    //Tại trang viết bài mới tìm vị trí nhập tiêu đề, nội dung bài viết
    $post_title = $driver->findElement(WebDriverBy::name('post_title'));

    //Auto nhập nội dung cho title
    $post_title->sendKeys($obj->title);

    //Blog không dùng tinymce có thể tìm kiếm phần tử nhập nội dung qua id sau:
    //$post_description_element = $driver->findElement(WebDriverBy::id('content'));

    //Dùng TinyMCE cần tìm kiếm id phần tử nội dung qua iframe.
    $iframe_content = $driver->findElement(WebDriverBy::id('content_ifr'));
    $driver->switchTo()->frame($iframe_content);
    $post_description_element = $driver->findElement(WebDriverBy::id('tinymce'));

    //Auto nhập nội dung cho description
    $post_description_element->sendKeys($obj->description);

    //Chuyển về lại default content (#iframe);
    $driver->switchTo()->defaultContent();
    
    //Chuyển sang chế độ nhập mã HTML
    $tab_editor_edit_html = $driver->findElement(WebDriverBy::id('content-html'));
    $tab_editor_edit_html->click();

    $driver->switchTo()->frame($iframe_content);
    //Chèn mã HTML hình ảnh vừa upload và bài viết;
    $post_description_element->sendKeys('<img style="width:400px;border:2px solid #CCC;padding:8px" src="'.$obj->media.'">');

    //Chuyển về lại default content (#iframe);
    $driver->switchTo()->defaultContent();

    //Chuyển sang chế độ nhập nội dung
    $tab_editor_edit_content = $driver->findElement(WebDriverBy::id('content-tmce'));
    $tab_editor_edit_content->click();

    //Save chế độ nháp bài viết: xử lý submit nếu không hỗ trợ thì xử lý click();
    try{
        $driver->findElement(WebDriverBy::id('save-post'))->submit();
    }catch(Exception $e){
        $driver->findElement(WebDriverBy::id('save-post'))->click();
    }
    

    // Chờ đến khi phần tử có class "notice-success" hiển thị tức thông báo update thành công.
    $successNotice = $driver->wait()->until(
        WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('.notice-success'))
    );

    return $successNotice->isDisplayed();
}
function test_upload_file_from_media($driver,$file_path,$current_site_infor=null){
    if(empty($current_site_infor)) return '';

    $current_domain=$current_site_infor['domain'];

    // Đường dẫn đến tệp tin hình ảnh trên máy của bạn
    $imagePath = $file_path;

    // Tải lên hình ảnh bằng cách gửi yêu cầu POST đến wp-admin/async-upload.php?browser-uploader (Sử dụng upload mặc định của browser)
    $driver->get($current_domain.'/wp-admin/media-new.php?browser-uploader');
    $driver->findElement(WebDriverBy::id('async-upload'))->sendKeys($imagePath);

    $upload_button = $driver->findElement(WebDriverBy::id('html-upload'));
    $upload_button->click();

    // Upload thành công link chuyển tới upload.php
    $is_uploaded=$driver->wait()->until(
        WebDriverExpectedCondition::urlContains('upload.php'),false
    );
    //Upload thành công => lấy url hình ảnh upload;
    $image_url='';
    if($is_uploaded){
            //TODO: ảnh đầu tiên => là ảnh mới upload;
            $first_media = $driver->wait()->until(
                WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::cssSelector('.attachments-wrapper .thumbnail'))
            );
            $first_media->click();
            
            $image_infor = $driver->findElement(WebDriverBy::id('attachment-details-two-column-copy-link'));
            $image_url=$image_infor->getAttribute('value');
    }

    return $image_url;
}

function is_logged($driver,$current_site_infor=null){
    if(empty($current_site_infor)) return '';

    $current_domain=$current_site_infor['domain'];

    $driver->get($current_domain.'/wp-login.php');

    $username = $driver->findElement(WebDriverBy::name('log'));
    $password = $driver->findElement(WebDriverBy::name('pwd'));

    $username->sendKeys($current_site_infor['user_name']);
    $password->sendKeys($current_site_infor['password']);

    $password->submit();

    $is_logged=true;
    $is_logged=$driver->wait()->until(
        WebDriverExpectedCondition::urlContains('wp-admin'),false
    );
    return $is_logged;
}
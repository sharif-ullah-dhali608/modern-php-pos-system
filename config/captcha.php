<?php
session_start();

if (ob_get_length()) ob_clean();


$random_num = rand(10000, 99999);
$_SESSION['captcha_code'] = $random_num;


$layer = imagecreatetruecolor(120, 40);

$bg_color = imagecolorallocate($layer, 243, 244, 246);
$text_color = imagecolorallocate($layer, 79, 70, 229); 
$line_color = imagecolorallocate($layer, 200, 200, 200);

imagefill($layer, 0, 0, $bg_color);


for($i=0; $i<6; $i++) {
    imageline($layer, 0, rand()%40, 120, rand()%40, $line_color);
}


imagestring($layer, 5, 35, 12, $random_num, $text_color);


header("Content-Type: image/jpeg");


header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// ইমেজ আউটপুট
imagejpeg($layer);
imagedestroy($layer);
exit();
?>
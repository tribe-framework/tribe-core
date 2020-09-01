<?php
session_start();

include_once('init.php');
include_once(ABSOLUTE_PATH.'/includes/captcha.class.php');
$captcha = new captcha;

$captcha_code=$_GET['captcha_code'];
if ($captcha_code) {
	$string=$captcha->get_captcha_string_from_code($captcha_code);
	if (!$string)
		$string = substr(md5(microtime()),rand(0,26),8);
}
else
	$string = substr(md5(microtime()),rand(0,26),8);


$font_size = 21;
$src_img = @imagecreate((40+strlen($string)*$font_size/1.5), $font_size+10);
$background = imagecolorallocate($src_img, 255, 255, 255);
$text_colour = imagecolorallocate($src_img, 33, 33, 33);
imagettftext($src_img, $font_size, 0, 20, $font_size+4, $text_colour, ABSOLUTE_PATH.'/admin/img/captcha.ttf', $string);

$magnify = rand(2,6);
$w = imagesx($src_img); 
$h = imagesy($src_img); 
$dst_img = imagecreatetruecolor($w * $magnify, $h * $magnify);
imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $w * $magnify, $h * $magnify, $w, $h);
$src_img = $dst_img;
$w *= $magnify;
$h *= $magnify;
$new_lh = abs($h * 0.66); 
$new_rh = $h ; 
$step = abs((($new_rh - $new_lh) / 2) / $w);
$from_top = ($new_rh - $new_lh) / 2 ; 
$dst_img = imagecreatetruecolor($w, $new_rh);
$bg_colour = imagecolorallocate($dst_img, 255, 255, 255); 
imagefill($dst_img, 0, 0, $bg_colour); 
for ($i = 0 ; $i < $w ; $i ++)
	imagecopyresampled($dst_img, $src_img, $i, $from_top - $step * $i, $i, 0, 1, $new_lh + $step * $i * 2, 1, $h); 
$src_img = $dst_img;
$dst_img = imagecreatetruecolor($w / $magnify  * 0.85, $new_rh / $magnify);
imagecopyresampled ($dst_img, $src_img, 0, 0, 0, 0, $w / $magnify * 0.85, $h / $magnify, $w, $h);
imagefilter($dst_img, IMG_FILTER_GAUSSIAN_BLUR);
imagefilter($dst_img, IMG_FILTER_EMBOSS);

header("Content-type: image/png");
imagepng($dst_img);
imagedestroy($dst_img);
?>
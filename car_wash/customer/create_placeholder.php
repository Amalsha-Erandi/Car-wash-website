<?php
// Create a placeholder image directory if it doesn't exist
$dir = 'images';
if (!file_exists($dir)) {
    mkdir($dir, 0777, true);
}

// Create a 400x300 image with a light blue background
$width = 400;
$height = 300;
$image = imagecreatetruecolor($width, $height);

// Colors
$bg_color = imagecolorallocate($image, 240, 248, 255); // Light blue background
$text_color = imagecolorallocate($image, 70, 130, 180); // Steel blue text
$border_color = imagecolorallocate($image, 100, 149, 237); // Cornflower blue border

// Fill the background
imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

// Draw a border
imagerectangle($image, 0, 0, $width - 1, $height - 1, $border_color);
imagerectangle($image, 5, 5, $width - 6, $height - 6, $border_color);

// Add text
$text = "Product Image";
$font_size = 5;
$text_width = imagefontwidth($font_size) * strlen($text);
$text_height = imagefontheight($font_size);

// Center the text
$x = ($width - $text_width) / 2;
$y = ($height - $text_height) / 2;

// Draw the text
imagestring($image, $font_size, $x, $y, $text, $text_color);

// Save the image
$filename = $dir . '/product-placeholder.jpg';
imagejpeg($image, $filename, 90);
imagedestroy($image);

echo "Placeholder image created at: $filename";
?> 
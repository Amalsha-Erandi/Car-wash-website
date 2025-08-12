<?php
// Create uploads directory if it doesn't exist
$uploadsDir = 'uploads/products';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
}

// Copy the existing placeholder image from customer directory
$sourcePath = '../customer/images/organic-cosmetic-product-with-dreamy-aesthetic-fresh-background.jpg';
$placeholderPath = $uploadsDir . '/placeholder.jpg';

if (copy($sourcePath, $placeholderPath)) {
    echo "Placeholder image created at: " . $placeholderPath;
} else {
    echo "Error creating placeholder image";
}
?> 
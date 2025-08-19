<?php
// Debug script to test image upload functionality
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

// Test image processing
function testImageProcessing($filePath) {
    echo "Testing image: $filePath\n";
    
    if (!file_exists($filePath)) {
        echo "ERROR: File does not exist\n";
        return false;
    }
    
    // Check file size
    $fileSize = filesize($filePath);
    $maxSize = MAX_FILE_SIZE;
    echo "File size: " . round($fileSize / 1024 / 1024, 2) . "MB (max: " . round($maxSize / 1024 / 1024, 2) . "MB)\n";
    
    if ($fileSize > $maxSize) {
        echo "ERROR: File too large\n";
        return false;
    }
    
    // Check MIME type
    $mimeType = mime_content_type($filePath);
    echo "MIME type: $mimeType\n";
    
    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
        echo "ERROR: MIME type not allowed\n";
        return false;
    }
    
    // Check if GD extension is available
    if (!extension_loaded('gd')) {
        echo "ERROR: GD extension not loaded\n";
        return false;
    }
    
    // Test image creation
    $imageInfo = getimagesize($filePath);
    if (!$imageInfo) {
        echo "ERROR: Could not get image info\n";
        return false;
    }
    
    echo "Image dimensions: {$imageInfo[0]}x{$imageInfo[1]}\n";
    echo "Image type: {$imageInfo['mime']}\n";
    
    // Test image resource creation
    $source = null;
    switch ($imageInfo['mime']) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($filePath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($filePath);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($filePath);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $source = imagecreatefromwebp($filePath);
            } else {
                echo "ERROR: WebP support not available\n";
                return false;
            }
            break;
        default:
            echo "ERROR: Unsupported image type\n";
            return false;
    }
    
    if (!$source) {
        echo "ERROR: Could not create image resource\n";
        return false;
    }
    
    echo "SUCCESS: Image resource created successfully\n";
    imagedestroy($source);
    
    return true;
}

// Test upload directory
echo "=== Testing Upload Directory ===\n";
$uploadDir = ROOT_PATH . 'public/uploads/';
echo "Upload directory: $uploadDir\n";
echo "Directory exists: " . (is_dir($uploadDir) ? 'Yes' : 'No') . "\n";
echo "Directory writable: " . (is_writable($uploadDir) ? 'Yes' : 'No') . "\n";

if (!is_dir($uploadDir)) {
    echo "Creating upload directory...\n";
    if (mkdir($uploadDir, 0755, true)) {
        echo "SUCCESS: Directory created\n";
    } else {
        echo "ERROR: Could not create directory\n";
    }
}

// Test constants
echo "\n=== Testing Constants ===\n";
echo "MAX_FILE_SIZE: " . MAX_FILE_SIZE . " bytes (" . round(MAX_FILE_SIZE / 1024 / 1024, 2) . "MB)\n";
echo "MAX_UPLOAD_COUNT: " . MAX_UPLOAD_COUNT . "\n";
echo "ALLOWED_IMAGE_TYPES: " . implode(', ', ALLOWED_IMAGE_TYPES) . "\n";

// Test GD extension
echo "\n=== Testing GD Extension ===\n";
echo "GD extension loaded: " . (extension_loaded('gd') ? 'Yes' : 'No') . "\n";
if (extension_loaded('gd')) {
    echo "GD version: " . gd_info()['GD Version'] . "\n";
    echo "Supported formats: " . implode(', ', array_keys(array_filter(gd_info(), function($v) { return $v === true; }))) . "\n";
}

// Test with sample images if they exist
echo "\n=== Testing Sample Images ===\n";
$sampleImages = [
    '../public/img/stamp1.jpg',
    '../public/img/stamp2.jpg',
    '../public/img/stamp3.jpg'
];

foreach ($sampleImages as $image) {
    if (file_exists($image)) {
        echo "\n";
        testImageProcessing($image);
    }
}

// Test upload directory permissions
echo "\n=== Testing Upload Permissions ===\n";
$testFile = $uploadDir . 'test_' . uniqid() . '.txt';
if (file_put_contents($testFile, 'test')) {
    echo "SUCCESS: Can write to upload directory\n";
    unlink($testFile);
} else {
    echo "ERROR: Cannot write to upload directory\n";
}

echo "\n=== Debug Complete ===\n";
?>

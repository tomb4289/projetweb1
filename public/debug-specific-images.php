<?php
// Debug script to test specific image processing issues
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

// Test specific image processing step by step
function testImageStepByStep($filePath) {
    echo "\n=== Testing Image: " . basename($filePath) . " ===\n";
    
    if (!file_exists($filePath)) {
        echo "ERROR: File does not exist\n";
        return false;
    }
    
    // Step 1: Basic file info
    echo "Step 1: Basic file info\n";
    $fileSize = filesize($filePath);
    echo "  - File size: " . round($fileSize / 1024 / 1024, 2) . "MB\n";
    echo "  - File permissions: " . substr(sprintf('%o', fileperms($filePath)), -4) . "\n";
    echo "  - File readable: " . (is_readable($filePath) ? 'Yes' : 'No') . "\n";
    
    // Step 2: MIME type detection
    echo "Step 2: MIME type detection\n";
    $mimeType = mime_content_type($filePath);
    echo "  - MIME type: $mimeType\n";
    echo "  - Allowed: " . (in_array($mimeType, ALLOWED_IMAGE_TYPES) ? 'Yes' : 'No') . "\n";
    
    // Step 3: Image info
    echo "Step 3: Image info\n";
    $imageInfo = getimagesize($filePath);
    if (!$imageInfo) {
        echo "  - ERROR: Could not get image info\n";
        return false;
    }
    echo "  - Dimensions: {$imageInfo[0]}x{$imageInfo[1]}\n";
    echo "  - Image type: {$imageInfo['mime']}\n";
    echo "  - Bits per pixel: {$imageInfo['bits']}\n";
    echo "  - Channels: {$imageInfo['channels']}\n";
    
    // Step 4: GD extension test
    echo "Step 4: GD extension test\n";
    if (!extension_loaded('gd')) {
        echo "  - ERROR: GD extension not loaded\n";
        return false;
    }
    echo "  - GD version: " . gd_info()['GD Version'] . "\n";
    
    // Step 5: Image resource creation
    echo "Step 5: Image resource creation\n";
    $source = null;
    try {
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
                    echo "  - ERROR: WebP support not available\n";
                    return false;
                }
                break;
            default:
                echo "  - ERROR: Unsupported image type\n";
                return false;
        }
        
        if (!$source) {
            echo "  - ERROR: Could not create image resource\n";
            return false;
        }
        
        echo "  - SUCCESS: Image resource created\n";
        echo "  - Resource width: " . imagesx($source) . "\n";
        echo "  - Resource height: " . imagesy($source) . "\n";
        
    } catch (Exception $e) {
        echo "  - ERROR: Exception during image creation: " . $e->getMessage() . "\n";
        return false;
    }
    
    // Step 6: Test resize operation
    echo "Step 6: Test resize operation\n";
    try {
        $targetWidth = 800;
        $targetHeight = 600;
        
        // Calculate dimensions
        $originalWidth = imagesx($source);
        $originalHeight = imagesy($source);
        $ratio = min($targetWidth / $originalWidth, $targetHeight / $originalHeight);
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);
        
        echo "  - Original: {$originalWidth}x{$originalHeight}\n";
        echo "  - Target: {$targetWidth}x{$targetHeight}\n";
        echo "  - Calculated: {$newWidth}x{$newHeight}\n";
        
        // Create target canvas
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        if (!$target) {
            echo "  - ERROR: Could not create target canvas\n";
            imagedestroy($source);
            return false;
        }
        
        // Set background
        $white = imagecolorallocate($target, 255, 255, 255);
        imagefill($target, 0, 0, $white);
        
        // Resize
        $success = imagecopyresampled(
            $target, $source,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $originalWidth, $originalHeight
        );
        
        if (!$success) {
            echo "  - ERROR: Image resize failed\n";
            imagedestroy($source);
            imagedestroy($target);
            return false;
        }
        
        echo "  - SUCCESS: Image resize completed\n";
        
        // Clean up
        imagedestroy($source);
        imagedestroy($target);
        
    } catch (Exception $e) {
        echo "  - ERROR: Exception during resize: " . $e->getMessage() . "\n";
        if ($source) imagedestroy($source);
        return false;
    }
    
    echo "  - SUCCESS: All tests passed for " . basename($filePath) . "\n";
    return true;
}

// Test memory usage
echo "=== Memory Usage Test ===\n";
echo "Memory limit: " . ini_get('memory_limit') . "\n";
echo "Current memory usage: " . round(memory_get_usage() / 1024 / 1024, 2) . "MB\n";
echo "Peak memory usage: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . "MB\n";

// Test upload directory
echo "\n=== Upload Directory Test ===\n";
$uploadDir = ROOT_PATH . 'public/uploads/';
echo "Upload directory: $uploadDir\n";
echo "Directory exists: " . (is_dir($uploadDir) ? 'Yes' : 'No') . "\n";
echo "Directory writable: " . (is_writable($uploadDir) ? 'Yes' : 'No') . "\n";

// Test with sample images
echo "\n=== Testing Sample Images ===\n";
$sampleImages = [
    '../public/img/stamp1.jpg',
    '../public/img/stamp2.jpg',
    '../public/img/stamp3.jpg',
    '../public/img/stamp4.jpg',
    '../public/img/stamp5.jpg'
];

$successCount = 0;
$totalCount = 0;

foreach ($sampleImages as $image) {
    if (file_exists($image)) {
        $totalCount++;
        if (testImageStepByStep($image)) {
            $successCount++;
        }
    }
}

echo "\n=== Summary ===\n";
echo "Total images tested: $totalCount\n";
echo "Successful: $successCount\n";
echo "Failed: " . ($totalCount - $successCount) . "\n";

// Test memory after processing
echo "\nFinal memory usage: " . round(memory_get_usage() / 1024 / 1024, 2) . "MB\n";
echo "Peak memory usage: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . "MB\n";

echo "\n=== Debug Complete ===\n";
?>

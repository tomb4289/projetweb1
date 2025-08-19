<?php
echo "=== Simple Debug Script ===\n";

echo "PHP Version: " . PHP_VERSION . "\n";
echo "Memory limit: " . ini_get('memory_limit') . "\n";
echo "Upload max filesize: " . ini_get('upload_max_filesize') . "\n";
echo "Post max size: " . ini_get('post_max_size') . "\n";

echo "\n=== GD Extension Test ===\n";
if (extension_loaded('gd')) {
    echo "✓ GD extension loaded\n";
    $gdInfo = gd_info();
    echo "GD Version: " . $gdInfo['GD Version'] . "\n";
} else {
    echo "✗ GD extension NOT loaded\n";
}

echo "\n=== Config Test ===\n";
try {
    require_once __DIR__ . '/../config.php';
    echo "✓ Config loaded successfully\n";
    echo "MAX_FILE_SIZE: " . (defined('MAX_FILE_SIZE') ? MAX_FILE_SIZE : 'NOT DEFINED') . "\n";
    echo "MAX_UPLOAD_COUNT: " . (defined('MAX_UPLOAD_COUNT') ? MAX_UPLOAD_COUNT : 'NOT DEFINED') . "\n";
} catch (Exception $e) {
    echo "✗ Config error: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "✗ Fatal config error: " . $e->getMessage() . "\n";
}

echo "\n=== Simple Image Test ===\n";
$testImage = '../public/img/stamp1.jpg';
if (file_exists($testImage)) {
    echo "✓ Test image exists: $testImage\n";
    
    $fileSize = filesize($testImage);
    echo "File size: " . round($fileSize / 1024 / 1024, 2) . "MB\n";
    
    $imageInfo = @getimagesize($testImage);
    if ($imageInfo) {
        echo "✓ Image info: {$imageInfo[0]}x{$imageInfo[1]}, Type: {$imageInfo['mime']}\n";
        
        if (extension_loaded('gd')) {
            try {
                $source = @imagecreatefromjpeg($testImage);
                if ($source) {
                    echo "✓ GD image creation successful\n";
                    imagedestroy($source);
                } else {
                    echo "✗ GD image creation failed\n";
                }
            } catch (Exception $e) {
                echo "✗ GD exception: " . $e->getMessage() . "\n";
            } catch (Error $e) {
                echo "✗ GD fatal error: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "✗ Could not get image info\n";
    }
} else {
    echo "✗ Test image not found: $testImage\n";
}

echo "\n=== Upload Directory Test ===\n";
$uploadDir = __DIR__ . '/../public/uploads/';
echo "Upload directory: $uploadDir\n";
echo "Directory exists: " . (is_dir($uploadDir) ? 'Yes' : 'No') . "\n";
echo "Directory writable: " . (is_writable($uploadDir) ? 'Yes' : 'No') . "\n";

echo "\n=== Memory Test ===\n";
echo "Current memory: " . round(memory_get_usage() / 1024 / 1024, 2) . "MB\n";
echo "Peak memory: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . "MB\n";

echo "\n=== Debug Complete ===\n";
?>

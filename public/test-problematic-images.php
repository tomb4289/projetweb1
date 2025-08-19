<?php
// Test script to identify problematic images
echo "=== Testing Problematic Images ===\n";

// Test with the actual images you have
$testImages = [
    '../img/stamp1.jpg',
    '../img/stamp2.jpg', 
    '../img/stamp3.jpg',
    '../img/stamp4.jpg',
    '../img/stamp5.jpg',
    '../img/stamp6.jpg',
    '../img/stamp7.jpg',
    '../img/stamp8.jpg',
    '../img/stamp9.jpg',
    '../img/stamp10.jpg',
    '../img/stamp11.jpg',
    '../img/stamp12.jpg'
];

echo "PHP Upload Limits:\n";
echo "- upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "- post_max_size: " . ini_get('post_max_size') . "\n";
echo "- max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "- memory_limit: " . ini_get('memory_limit') . "\n\n";

$problematicImages = [];

foreach ($testImages as $imagePath) {
    echo "Testing: " . basename($imagePath) . "\n";
    
    if (!file_exists($imagePath)) {
        echo "  ✗ File not found\n";
        continue;
    }
    
    $fileSize = filesize($imagePath);
    $fileSizeMB = round($fileSize / 1024 / 1024, 2);
    echo "  Size: {$fileSizeMB}MB\n";
    
    // Check if file size exceeds PHP limits
    $uploadLimit = return_bytes(ini_get('upload_max_filesize'));
    $postLimit = return_bytes(ini_get('post_max_size'));
    
    if ($fileSize > $uploadLimit) {
        echo "  ✗ EXCEEDS upload_max_filesize (" . ini_get('upload_max_filesize') . ")\n";
        $problematicImages[] = [
            'file' => basename($imagePath),
            'size' => $fileSizeMB,
            'issue' => 'Exceeds upload limit'
        ];
    } elseif ($fileSize > $postLimit) {
        echo "  ✗ EXCEEDS post_max_size (" . ini_get('post_max_size') . ")\n";
        $problematicImages[] = [
            'file' => basename($imagePath),
            'size' => $fileSizeMB,
            'issue' => 'Exceeds POST limit'
        ];
    } else {
        echo "  ✓ Within limits\n";
        
        // Test GD processing
        if (extension_loaded('gd')) {
            try {
                $imageInfo = @getimagesize($imagePath);
                if ($imageInfo) {
                    echo "  ✓ Image info: {$imageInfo[0]}x{$imageInfo[1]}, {$imageInfo['mime']}\n";
                    
                    // Test GD creation
                    $source = null;
                    switch ($imageInfo['mime']) {
                        case 'image/jpeg':
                            $source = @imagecreatefromjpeg($imagePath);
                            break;
                        case 'image/png':
                            $source = @imagecreatefrompng($imagePath);
                            break;
                        case 'image/gif':
                            $source = @imagecreatefromgif($imagePath);
                            break;
                        case 'image/webp':
                            if (function_exists('imagecreatefromwebp')) {
                                $source = @imagecreatefromwebp($imagePath);
                            }
                            break;
                    }
                    
                    if ($source) {
                        echo "  ✓ GD creation successful\n";
                        imagedestroy($source);
                    } else {
                        echo "  ✗ GD creation failed\n";
                        $problematicImages[] = [
                            'file' => basename($imagePath),
                            'size' => $fileSizeMB,
                            'issue' => 'GD creation failed'
                        ];
                    }
                } else {
                    echo "  ✗ Could not get image info\n";
                    $problematicImages[] = [
                        'file' => basename($imagePath),
                        'size' => $fileSizeMB,
                        'issue' => 'Invalid image'
                    ];
                }
            } catch (Exception $e) {
                echo "  ✗ Exception: " . $e->getMessage() . "\n";
                $problematicImages[] = [
                    'file' => basename($imagePath),
                    'size' => $fileSizeMB,
                    'issue' => 'Exception: ' . $e->getMessage()
                ];
            } catch (Error $e) {
                echo "  ✗ Fatal error: " . $e->getMessage() . "\n";
                $problematicImages[] = [
                    'file' => basename($imagePath),
                    'size' => $fileSizeMB,
                    'issue' => 'Fatal error: ' . $e->getMessage()
                ];
            }
        }
    }
    
    echo "\n";
}

// Summary
echo "=== SUMMARY ===\n";
if (empty($problematicImages)) {
    echo "✓ All images are within limits and should work\n";
} else {
    echo "✗ Found " . count($problematicImages) . " problematic images:\n";
    foreach ($problematicImages as $img) {
        echo "- {$img['file']} ({$img['size']}MB): {$img['issue']}\n";
    }
}

echo "\n=== RECOMMENDATIONS ===\n";
$uploadLimit = return_bytes(ini_get('upload_max_filesize'));
$postLimit = return_bytes(ini_get('post_max_size'));

if ($uploadLimit < 5 * 1024 * 1024) { // 5MB
    echo "1. Increase upload_max_filesize to at least 5M\n";
}
if ($postLimit < 10 * 1024 * 1024) { // 10MB
    echo "2. Increase post_max_size to at least 10M\n";
}

echo "3. Check your php.ini file or .htaccess for these settings\n";
echo "4. Restart your web server after making changes\n";

// Helper function
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}
?>

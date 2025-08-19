<?php
// Test PHP configuration for image uploads
echo "<h1>PHP Configuration Test for Image Uploads</h1>";

echo "<h2>File Upload Settings</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";

$uploadSettings = [
    'file_uploads' => 'File uploads enabled',
    'upload_max_filesize' => 'Max file size',
    'post_max_size' => 'Max POST size',
    'max_file_uploads' => 'Max file uploads',
    'memory_limit' => 'Memory limit',
    'max_execution_time' => 'Max execution time',
    'max_input_time' => 'Max input time'
];

foreach ($uploadSettings as $setting => $description) {
    $value = ini_get($setting);
    $status = 'OK';
    
    if ($setting === 'file_uploads' && $value != '1') {
        $status = 'WARNING: File uploads disabled';
    } elseif ($setting === 'upload_max_filesize') {
        $valueBytes = return_bytes($value);
        if ($valueBytes < 5 * 1024 * 1024) { // 5MB
            $status = 'WARNING: Too low for 5MB images';
        }
    } elseif ($setting === 'post_max_size') {
        $valueBytes = return_bytes($value);
        if ($valueBytes < 10 * 1024 * 1024) { // 10MB
            $status = 'WARNING: Too low for multiple images';
        }
    }
    
    echo "<tr><td>$description</td><td>$value</td><td>$status</td></tr>";
}

echo "</table>";

echo "<h2>GD Extension</h2>";
if (extension_loaded('gd')) {
    $gdInfo = gd_info();
    echo "<p><strong>GD Version:</strong> " . $gdInfo['GD Version'] . "</p>";
    echo "<p><strong>Supported Formats:</strong></p>";
    echo "<ul>";
    foreach ($gdInfo as $key => $value) {
        if (is_bool($value) && $value) {
            echo "<li>$key</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'><strong>ERROR: GD extension not loaded!</strong></p>";
}

echo "<h2>Image Processing Test</h2>";
$testImage = '../public/img/stamp1.jpg';
if (file_exists($testImage)) {
    echo "<p>Testing with: $testImage</p>";
    
    $imageInfo = getimagesize($testImage);
    if ($imageInfo) {
        echo "<p>Image info: {$imageInfo[0]}x{$imageInfo[1]}, Type: {$imageInfo['mime']}</p>";
        
        // Test GD functions
        try {
            $source = imagecreatefromjpeg($testImage);
            if ($source) {
                echo "<p style='color: green;'>✓ GD image creation successful</p>";
                
                // Test resize
                $target = imagecreatetruecolor(100, 100);
                if ($target) {
                    $success = imagecopyresampled($target, $source, 0, 0, 0, 0, 100, 100, $imageInfo[0], $imageInfo[1]);
                    if ($success) {
                        echo "<p style='color: green;'>✓ GD resize successful</p>";
                    } else {
                        echo "<p style='color: red;'>✗ GD resize failed</p>";
                    }
                    imagedestroy($target);
                }
                
                imagedestroy($source);
            } else {
                echo "<p style='color: red;'>✗ GD image creation failed</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ GD error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Could not get image info</p>";
    }
} else {
    echo "<p style='color: orange;'>Test image not found: $testImage</p>";
}

echo "<h2>Error Reporting</h2>";
echo "<p><strong>Error reporting:</strong> " . ini_get('error_reporting') . "</p>";
echo "<p><strong>Display errors:</strong> " . ini_get('display_errors') . "</p>";
echo "<p><strong>Log errors:</strong> " . ini_get('log_errors') . "</p>";
echo "<p><strong>Error log:</strong> " . ini_get('error_log') . "</p>";

echo "<h2>Session Test</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p style='color: green;'>✓ Sessions are active</p>";
} else {
    echo "<p style='color: red;'>✗ Sessions are not active</p>";
}

// Helper function to convert size strings to bytes
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}

echo "<h2>Test Complete</h2>";
echo "<p>Check the browser console and server error logs for additional information.</p>";
?>

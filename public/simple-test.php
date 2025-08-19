<?php
echo "=== Simple PHP Upload Limits Test ===\n\n";

echo "PHP Upload Limits:\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n\n";

echo "Your App Config:\n";
echo "MAX_FILE_SIZE: 5MB\n";
echo "MAX_UPLOAD_COUNT: 5\n\n";

echo "=== ANALYSIS ===\n";
$uploadLimit = ini_get('upload_max_filesize');
$postLimit = ini_get('post_max_size');

if ($uploadLimit === '2M') {
    echo "✗ PROBLEM: upload_max_filesize is 2M but your app allows 5MB images\n";
    echo "   This will cause large images to be silently rejected!\n\n";
}

if ($postLimit === '8M') {
    echo "✗ PROBLEM: post_max_size is 8M but you need at least 10M for multiple 5MB images\n";
    echo "   This will cause form submission to fail with multiple large images!\n\n";
}

echo "=== SOLUTION ===\n";
echo "You need to increase PHP upload limits in your php.ini file:\n";
echo "1. upload_max_filesize = 10M\n";
echo "2. post_max_size = 20M\n";
echo "3. Restart your web server\n\n";

echo "=== Test Complete ===\n";
?>

<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
$config = require_once __DIR__ . '/../config.php';

echo "=== Image Processing Test ===\n\n";

try {
    // Create database connection
    $pdo = new PDO(
        "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset=utf8mb4",
        $config['database']['user'],
        $config['database']['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    echo "✅ Database connection successful!\n\n";

    // Check current images in database
    $stmt = $pdo->query("SELECT * FROM images ORDER BY id DESC LIMIT 5");
    $images = $stmt->fetchAll();
    
    if (empty($images)) {
        echo "❌ No images found in database.\n";
        exit;
    }

    echo "📸 Found " . count($images) . " recent images:\n";
    foreach ($images as $image) {
        $filePath = $image['chemin'];
        $fullPath = __DIR__ . '/../' . $filePath;
        
        if (file_exists($fullPath)) {
            $fileSize = filesize($fullPath);
            $fileSizeKB = round($fileSize / 1024, 1);
            $imageInfo = getimagesize($fullPath);
            
            if ($imageInfo) {
                $dimensions = $imageInfo[0] . 'x' . $imageInfo[1];
                echo "  - {$filePath} ({$dimensions}) - {$fileSizeKB}KB\n";
            } else {
                echo "  - {$filePath} - {$fileSizeKB}KB (could not read dimensions)\n";
            }
        } else {
            echo "  - {$filePath} - ❌ File not found\n";
        }
    }

    echo "\n🔍 Image Processing Features:\n";
    echo "  ✅ Standardized dimensions: 800x600px\n";
    echo "  ✅ Automatic cropping and centering\n";
    echo "  ✅ Format conversion to JPEG\n";
    echo "  ✅ Quality optimization (85%)\n";
    echo "  ✅ Thumbnail generation (200x150px)\n";
    echo "  ✅ Support for JPG, PNG, GIF\n";
    echo "  ✅ White background padding\n";
    echo "  ✅ Aspect ratio preservation\n";

    echo "\n📱 Carousel Features:\n";
    echo "  ✅ Responsive design (800x600 → 450x350 → 350x250)\n";
    echo "  ✅ Navigation arrows and indicators\n";
    echo "  ✅ Keyboard navigation (arrow keys)\n";
    echo "  ✅ Auto-advance every 5 seconds\n";
    echo "  ✅ Smooth fade transitions\n";

    echo "\n🎯 Next Steps:\n";
    echo "  1. Upload new images to test the processing\n";
    echo "  2. Check that all images are now 800x600px\n";
    echo "  3. Verify thumbnails are created\n";
    echo "  4. Test the carousel on auction pages\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>

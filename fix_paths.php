<?php
$dir = new RecursiveDirectoryIterator('views');
$it = new RecursiveIteratorIterator($dir);
foreach ($it as $file) {
    if ($file->getExtension() == 'php' || $file->getExtension() == 'js') {
        $content = file_get_contents($file->getPathname());
        $newContent = preg_replace('/\\/<\?= PROJECT_DIR \?>\\//', '<?= PROJECT_DIR ? \'/\' . PROJECT_DIR . \'/\' : \'/\' ?>', $content);
        if ($content !== $newContent) {
            file_put_contents($file->getPathname(), $newContent);
            echo 'Updated: ' . $file->getPathname() . PHP_EOL;
        }
    }
}

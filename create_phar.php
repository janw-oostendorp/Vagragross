#!/usr/bin/php -d phar.readonly=0
<?php
/**
 * create a phar
 * @see https://gist.github.com/spekkionu/8792084
 */

define('PHAR_FILE', 'vagragross.phar');
define('SOURCE_ROOT', realpath(__DIR__));
define('PHAR_PATH', SOURCE_ROOT . DIRECTORY_SEPARATOR . PHAR_FILE);

// class to whitelist files. Whitelisting will be more accurate then black listing
class MyRecursiveFilterIterator extends RecursiveFilterIterator
{

    public static $allowed_paths = [
        SOURCE_ROOT . DIRECTORY_SEPARATOR . 'vagragross.php',
        SOURCE_ROOT . DIRECTORY_SEPARATOR . 'includes',
        SOURCE_ROOT . DIRECTORY_SEPARATOR . 'vendor',
    ];

    public function accept()
    {
        foreach (self::$allowed_paths as $path) {
            if (strpos($this->current()->getPathname(), $path) === 0) {
                return true;
            }
        }
        return false;
    }
}

$dirItr = new RecursiveDirectoryIterator(SOURCE_ROOT, FilesystemIterator::SKIP_DOTS);
$filterItr = new MyRecursiveFilterIterator($dirItr);
$iterator = new RecursiveIteratorIterator($filterItr, RecursiveIteratorIterator::LEAVES_ONLY);

echo "Build vagragross phar\n";

if (file_exists(PHAR_PATH)) {
    unlink(PHAR_PATH);
}

$phar = new Phar(PHAR_PATH, 0, PHAR_FILE);
$phar->buildFromIterator($iterator, SOURCE_ROOT);
$phar->setStub($phar->createDefaultStub("vagragross.php"));

exit("Build complete\n");

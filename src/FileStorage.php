<?php

namespace NimblePHP\Debugbar;

use DebugBar\Storage\StorageInterface;

class FileStorage extends \DebugBar\Storage\FileStorage implements StorageInterface
{

    /**
     * File expiration time
     * @var int
     */
    public int $expirationTime = 86400;

    /**
     * Constructor
     * @param $dirname
     */
    public function __construct($dirname)
    {
        parent::__construct($dirname);

        $this->cleanOldDebugFiles($dirname);
    }

    /**
     * Clean old debug files
     * @param string $storagePath
     * @return void
     */
    private function cleanOldDebugFiles(string $storagePath): void {
        $deleted = 0;
        $files = glob($storagePath . '/*');

        foreach ($files as $file) {
            if (is_file($file) && (time() - filemtime($file)) > $this->expirationTime) {
                unlink($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            Debugbar::addMessage('Delete ' . $deleted . ' old debugbar cache files', 'Debugbar');
        }
    }

}
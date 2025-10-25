<?php
// public/autoload.php

declare(strict_types=1);

spl_autoload_register(function ($className) {
    // Convert namespace to file path
    $classFile = __DIR__ . '/../src/' . str_replace('\\', '/', $className) . '.php';
    
    // Debug: uncomment to see what's being loaded
    // error_log("Trying to load: $className from $classFile");
    
    if (file_exists($classFile)) {
        require_once $classFile;
        return true;
    }
    
    return false;
});
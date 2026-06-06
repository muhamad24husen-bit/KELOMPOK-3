<?php

require 'vendor/autoload.php';

use Spatie\Browsershot\Browsershot;

try {
    echo "Attempting to generate PDF...\n";
    Browsershot::html('<h1>Hello World</h1>')
        ->noSandbox()
        ->save('test.pdf');
    echo "Success! test.pdf created.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

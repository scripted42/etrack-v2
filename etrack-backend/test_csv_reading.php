<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentImport;

try {
    $file = new \Illuminate\Http\UploadedFile(
        'test_excel.csv',
        'test_excel.csv',
        'text/csv',
        null,
        true
    );
    
    $import = new StudentImport(1);
    
    // Test reading the file
    $data = Excel::toArray($import, $file);
    
    echo "Data read from CSV:\n";
    print_r($data);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}




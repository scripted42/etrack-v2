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
    
    // Test import process with debug
    echo "Starting import...\n";
    Excel::import($import, $file);
    echo "Import completed\n";
    
    echo "Imported count: " . $import->getImportedCount() . "\n";
    echo "Failed count: " . $import->getFailedCount() . "\n";
    echo "Errors: " . json_encode($import->getImportErrors()) . "\n";
    
    // Check if model method was called
    echo "Model method called: " . (method_exists($import, 'model') ? 'Yes' : 'No') . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}


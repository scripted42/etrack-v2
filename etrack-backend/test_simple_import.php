<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SimpleStudentImport;

try {
    $file = new \Illuminate\Http\UploadedFile(
        'test_excel.csv',
        'test_excel.csv',
        'text/csv',
        null,
        true
    );
    
    $import = new SimpleStudentImport(1);
    
    // Test import process
    echo "Starting import...\n";
    Excel::import($import, $file);
    echo "Import completed\n";
    
    echo "Imported count: " . $import->getImportedCount() . "\n";
    echo "Failed count: " . $import->getFailedCount() . "\n";
    echo "Errors: " . json_encode($import->getImportErrors()) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}




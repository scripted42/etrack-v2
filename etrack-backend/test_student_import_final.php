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
    
    // Test import process
    echo "Starting import...\n";
    Excel::import($import, $file);
    echo "Import completed\n";
    
    echo "Imported count: " . $import->getImportedCount() . "\n";
    echo "Failed count: " . $import->getFailedCount() . "\n";
    echo "Errors: " . json_encode($import->getImportErrors()) . "\n";
    
    // Check if model method was called
    echo "Model method called: " . (method_exists($import, 'model') ? 'Yes' : 'No') . "\n";
    
    // Check if WithHeadingRow trait is used
    echo "WithHeadingRow trait used: " . (in_array('Maatwebsite\Excel\Concerns\WithHeadingRow', class_uses($import)) ? 'Yes' : 'No') . "\n";
    
    // Check if ToModel trait is used
    echo "ToModel trait used: " . (in_array('Maatwebsite\Excel\Concerns\ToModel', class_uses($import)) ? 'Yes' : 'No') . "\n";
    
    // Check if WithHeadingRow is implemented
    echo "WithHeadingRow implemented: " . (in_array('Maatwebsite\Excel\Concerns\WithHeadingRow', class_implements($import)) ? 'Yes' : 'No') . "\n";
    
    // Check if ToModel is implemented
    echo "ToModel implemented: " . (in_array('Maatwebsite\Excel\Concerns\ToModel', class_implements($import)) ? 'Yes' : 'No') . "\n";
    
    // Check all interfaces
    echo "All interfaces: " . json_encode(class_implements($import)) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}


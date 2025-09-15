# E-Track Setup Script for Windows
# SMP Negeri 14 Surabaya

Write-Host "🚀 Setting up E-Track - Tracking & Manajemen Data SMPN 14 Surabaya" -ForegroundColor Green
Write-Host "==================================================================" -ForegroundColor Green

# Function to print colored output
function Write-Info {
    param([string]$Message)
    Write-Host "[INFO] $Message" -ForegroundColor Green
}

function Write-Warning {
    param([string]$Message)
    Write-Host "[WARNING] $Message" -ForegroundColor Yellow
}

function Write-Error {
    param([string]$Message)
    Write-Host "[ERROR] $Message" -ForegroundColor Red
}

function Write-Header {
    param([string]$Message)
    Write-Host "[SETUP] $Message" -ForegroundColor Blue
}

# Check if required tools are installed
function Test-Requirements {
    Write-Header "Checking system requirements..."
    
    $requirements = @{
        "PHP" = "php"
        "Composer" = "composer"
        "Node.js" = "node"
        "npm" = "npm"
        "MySQL" = "mysql"
    }
    
    foreach ($tool in $requirements.GetEnumerator()) {
        try {
            $null = Get-Command $tool.Value -ErrorAction Stop
            Write-Info "$($tool.Key) is installed"
        }
        catch {
            if ($tool.Key -eq "MySQL") {
                Write-Warning "$($tool.Key) is not installed. Please install MySQL 8.0+ first."
            } else {
                Write-Error "$($tool.Key) is not installed. Please install $($tool.Key) first."
                exit 1
            }
        }
    }
    
    Write-Info "All requirements checked!"
}

# Setup Laravel Backend
function Setup-Backend {
    Write-Header "Setting up Laravel Backend..."
    
    Set-Location etrack-backend
    
    # Install dependencies
    Write-Info "Installing PHP dependencies..."
    composer install
    
    # Copy environment file
    if (-not (Test-Path .env)) {
        Write-Info "Creating environment file..."
        Copy-Item .env.example .env
        Write-Warning "Please configure your database settings in .env file"
    }
    
    # Generate application key
    Write-Info "Generating application key..."
    php artisan key:generate
    
    # Run migrations and seeders
    Write-Info "Running database migrations..."
    php artisan migrate:fresh --seed
    
    Write-Info "Backend setup completed!"
    Set-Location ..
}

# Setup Vue.js Frontend
function Setup-Frontend {
    Write-Header "Setting up Vue.js Frontend..."
    
    Set-Location etrack-frontend
    
    # Install dependencies
    Write-Info "Installing Node.js dependencies..."
    npm install
    
    # Create environment file
    if (-not (Test-Path .env)) {
        Write-Info "Creating environment file..."
        "VITE_API_URL=http://localhost:8000/api" | Out-File -FilePath .env -Encoding UTF8
    }
    
    Write-Info "Frontend setup completed!"
    Set-Location ..
}

# Create database
function New-Database {
    Write-Header "Creating database..."
    
    $dbUser = Read-Host "Enter MySQL username (default: root)"
    if ([string]::IsNullOrEmpty($dbUser)) { $dbUser = "root" }
    
    $dbPass = Read-Host "Enter MySQL password" -AsSecureString
    $dbPassPlain = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($dbPass))
    
    $dbName = Read-Host "Enter database name (default: user_management_sekolah)"
    if ([string]::IsNullOrEmpty($dbName)) { $dbName = "user_management_sekolah" }
    
    try {
        $connectionString = "Server=localhost;Database=mysql;Uid=$dbUser;Pwd=$dbPassPlain;"
        $connection = New-Object System.Data.Odbc.OdbcConnection($connectionString)
        $connection.Open()
        
        $command = $connection.CreateCommand()
        $command.CommandText = "CREATE DATABASE IF NOT EXISTS $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        $command.ExecuteNonQuery()
        
        $connection.Close()
        Write-Info "Database created successfully!"
    }
    catch {
        Write-Error "Failed to create database. Please check your MySQL credentials."
        exit 1
    }
}

# Start development servers
function Start-Servers {
    Write-Header "Starting development servers..."
    
    Write-Info "Starting Laravel server on http://localhost:8000"
    Write-Info "Starting Vue.js server on http://localhost:5173"
    
    Write-Warning "Press Ctrl+C to stop all servers"
    
    # Start Laravel server
    $laravelJob = Start-Job -ScriptBlock {
        Set-Location $using:PWD\etrack-backend
        php artisan serve
    }
    
    # Start Vue.js server
    $vueJob = Start-Job -ScriptBlock {
        Set-Location $using:PWD\etrack-frontend
        npm run dev
    }
    
    # Wait for user to stop
    try {
        Wait-Job -Job $laravelJob, $vueJob
    }
    finally {
        # Cleanup jobs
        Stop-Job -Job $laravelJob, $vueJob
        Remove-Job -Job $laravelJob, $vueJob
    }
}

# Main setup function
function Show-Menu {
    Write-Host "E-Track Setup Script" -ForegroundColor Cyan
    Write-Host "===================" -ForegroundColor Cyan
    Write-Host "1. Check requirements"
    Write-Host "2. Setup Laravel backend"
    Write-Host "3. Setup Vue.js frontend"
    Write-Host "4. Create database"
    Write-Host "5. Start development servers"
    Write-Host "6. Exit"
    Write-Host ""
    
    $choice = Read-Host "Choose an option (1-6)"
    
    switch ($choice) {
        "1" { Test-Requirements }
        "2" { Setup-Backend }
        "3" { Setup-Frontend }
        "4" { New-Database }
        "5" { Start-Servers }
        "6" { 
            Write-Info "Setup cancelled by user"
            exit 0
        }
        default {
            Write-Error "Invalid option. Please choose 1-6."
            Show-Menu
        }
    }
}

# Run main function
Show-Menu

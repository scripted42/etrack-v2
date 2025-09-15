#!/bin/bash

# E-Track Setup Script
# SMP Negeri 14 Surabaya

echo "🚀 Setting up E-Track - Tracking & Manajemen Data SMPN 14 Surabaya"
echo "=================================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}[SETUP]${NC} $1"
}

# Check if required tools are installed
check_requirements() {
    print_header "Checking system requirements..."
    
    if ! command -v php &> /dev/null; then
        print_error "PHP is not installed. Please install PHP 8.3+ first."
        exit 1
    fi
    
    if ! command -v composer &> /dev/null; then
        print_error "Composer is not installed. Please install Composer first."
        exit 1
    fi
    
    if ! command -v node &> /dev/null; then
        print_error "Node.js is not installed. Please install Node.js 18+ first."
        exit 1
    fi
    
    if ! command -v npm &> /dev/null; then
        print_error "npm is not installed. Please install npm first."
        exit 1
    fi
    
    if ! command -v mysql &> /dev/null; then
        print_warning "MySQL is not installed. Please install MySQL 8.0+ first."
    fi
    
    print_status "All requirements checked!"
}

# Setup Laravel Backend
setup_backend() {
    print_header "Setting up Laravel Backend..."
    
    cd etrack-backend
    
    # Install dependencies
    print_status "Installing PHP dependencies..."
    composer install
    
    # Copy environment file
    if [ ! -f .env ]; then
        print_status "Creating environment file..."
        cp .env.example .env
        print_warning "Please configure your database settings in .env file"
    fi
    
    # Generate application key
    print_status "Generating application key..."
    php artisan key:generate
    
    # Run migrations and seeders
    print_status "Running database migrations..."
    php artisan migrate:fresh --seed
    
    print_status "Backend setup completed!"
    cd ..
}

# Setup Vue.js Frontend
setup_frontend() {
    print_header "Setting up Vue.js Frontend..."
    
    cd etrack-frontend
    
    # Install dependencies
    print_status "Installing Node.js dependencies..."
    npm install
    
    # Create environment file
    if [ ! -f .env ]; then
        print_status "Creating environment file..."
        echo "VITE_API_URL=http://localhost:8000/api" > .env
    fi
    
    print_status "Frontend setup completed!"
    cd ..
}

# Create database
create_database() {
    print_header "Creating database..."
    
    read -p "Enter MySQL username (default: root): " db_user
    db_user=${db_user:-root}
    
    read -s -p "Enter MySQL password: " db_pass
    echo
    
    read -p "Enter database name (default: user_management_sekolah): " db_name
    db_name=${db_name:-user_management_sekolah}
    
    mysql -u "$db_user" -p"$db_pass" -e "CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    
    if [ $? -eq 0 ]; then
        print_status "Database created successfully!"
    else
        print_error "Failed to create database. Please check your MySQL credentials."
        exit 1
    fi
}

# Start development servers
start_servers() {
    print_header "Starting development servers..."
    
    print_status "Starting Laravel server on http://localhost:8000"
    print_status "Starting Vue.js server on http://localhost:5173"
    
    print_warning "Press Ctrl+C to stop all servers"
    
    # Start Laravel server in background
    cd etrack-backend && php artisan serve &
    LARAVEL_PID=$!
    
    # Start Vue.js server in background
    cd ../etrack-frontend && npm run dev &
    VUE_PID=$!
    
    # Wait for user to stop
    wait
    
    # Cleanup on exit
    kill $LARAVEL_PID 2>/dev/null
    kill $VUE_PID 2>/dev/null
}

# Main setup function
main() {
    echo "E-Track Setup Script"
    echo "==================="
    echo "1. Check requirements"
    echo "2. Setup Laravel backend"
    echo "3. Setup Vue.js frontend"
    echo "4. Create database"
    echo "5. Start development servers"
    echo "6. Exit"
    echo
    
    read -p "Choose an option (1-6): " choice
    
    case $choice in
        1)
            check_requirements
            ;;
        2)
            setup_backend
            ;;
        3)
            setup_frontend
            ;;
        4)
            create_database
            ;;
        5)
            start_servers
            ;;
        6)
            print_status "Setup cancelled by user"
            exit 0
            ;;
        *)
            print_error "Invalid option. Please choose 1-6."
            main
            ;;
    esac
}

# Run main function
main

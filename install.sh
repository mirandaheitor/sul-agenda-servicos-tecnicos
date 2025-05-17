#!/bin/bash

echo "Sul Agenda - Environment Setup Script"
echo "==================================="
echo

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "Please run as root (use sudo)"
    exit 1
fi

# Function to check if a command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Check and install PHP
echo "Checking PHP installation..."
if ! command_exists php; then
    echo "Installing PHP and required extensions..."
    apt-get update
    apt-get install -y php php-cli php-fpm php-json php-pdo php-mysql php-zip php-gd php-mbstring php-curl php-xml php-bcmath
else
    echo "PHP is already installed"
fi

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "PHP version: $PHP_VERSION"

# Check and install MySQL
echo -e "\nChecking MySQL installation..."
if ! command_exists mysql; then
    echo "Installing MySQL..."
    apt-get install -y mysql-server
else
    echo "MySQL is already installed"
fi

# Check and install Apache
echo -e "\nChecking Apache installation..."
if ! command_exists apache2; then
    echo "Installing Apache..."
    apt-get install -y apache2
    a2enmod rewrite
    a2enmod headers
    systemctl restart apache2
else
    echo "Apache is already installed"
fi

# Create directories
echo -e "\nCreating required directories..."
mkdir -p assets/img/uploads logs
chmod 755 assets/img/uploads logs

# Set proper permissions
echo "Setting file permissions..."
chown -R www-data:www-data .
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod 755 install.sh

echo -e "\nEnvironment setup completed!"
echo "Next steps:"
echo "1. Configure your database settings in includes/config.php"
echo "2. Run: php install.php"
echo "3. Access the application through your web browser"
echo
echo "For more information, please read the README.md file."

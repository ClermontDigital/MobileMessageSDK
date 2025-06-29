#!/bin/bash

echo "🚀 Mobile Message PHP SDK - Testing Setup"
echo "=========================================="
echo

# Check if .env already exists
if [ -f ".env" ]; then
    echo "✅ .env file already exists"
    echo "   You can edit it directly or run this script to reconfigure"
    echo
    read -p "Do you want to reconfigure? (y/N): " reconfigure
    if [[ ! $reconfigure =~ ^[Yy]$ ]]; then
        echo "Setup cancelled."
        exit 0
    fi
    echo
fi

# Copy .env.example to .env
echo "📋 Creating .env file from template..."
cp .env.example .env

# Get user input for configuration
echo "🔧 Please provide your Mobile Message API credentials:"
echo

read -p "API Username: " username
read -s -p "API Password: " password
echo
read -p "Test Phone Number (default: 0400322583): " phone
read -p "Sender Phone Number (your registered sender): " sender

# Default phone if not provided
if [ -z "$phone" ]; then
    phone="0400322583"
fi

echo
read -p "Enable real SMS sending for tests? (y/N): " enable_sms
read -p "Enable bulk SMS tests? (y/N): " enable_bulk

# Set enable_sms to boolean
if [[ $enable_sms =~ ^[Yy]$ ]]; then
    enable_sms="true"
else
    enable_sms="false"
fi

# Set enable_bulk to boolean
if [[ $enable_bulk =~ ^[Yy]$ ]]; then
    enable_bulk="true"
else
    enable_bulk="false"
fi

# Update .env file
echo "📝 Updating .env file..."

# Use sed to replace values in .env file
sed -i.bak "s/API_USERNAME=.*/API_USERNAME=$username/" .env
sed -i.bak "s/API_PASSWORD=.*/API_PASSWORD=$password/" .env
sed -i.bak "s/TEST_PHONE_NUMBER=.*/TEST_PHONE_NUMBER=$phone/" .env
sed -i.bak "s/SENDER_PHONE_NUMBER=.*/SENDER_PHONE_NUMBER=$sender/" .env
sed -i.bak "s/ENABLE_REAL_SMS_TESTS=.*/ENABLE_REAL_SMS_TESTS=$enable_sms/" .env
sed -i.bak "s/ENABLE_BULK_SMS_TESTS=.*/ENABLE_BULK_SMS_TESTS=$enable_bulk/" .env

# Remove backup file
rm .env.bak

echo "✅ Configuration complete!"
echo
echo "📊 Your settings:"
echo "   API Username: $username"
echo "   API Password: [HIDDEN]"
echo "   Test Phone: $phone"
echo "   Sender Phone: $sender"
echo "   Real SMS Tests: $enable_sms"
echo "   Bulk SMS Tests: $enable_bulk"
echo

echo "🧪 Available test commands:"
echo "   composer test                    # Run unit tests only"
echo "   composer test -- --testsuite Integration  # Run integration tests"
echo "   php examples/test_example.php    # Run comprehensive test script"
echo "   php examples/basic_example.php   # Test basic SMS sending"
echo "   php examples/bulk_example.php    # Test bulk SMS sending"
echo

if [ "$enable_sms" = "true" ]; then
    echo "⚠️  WARNING: Real SMS tests are enabled. These will:"
    echo "   - Send actual SMS messages to $phone"
    echo "   - Use credits from your Mobile Message account"
    echo "   - Charge according to your Mobile Message pricing"
    echo
    if [ "$enable_bulk" = "true" ]; then
        echo "⚠️  BULK SMS tests are also enabled. These will:"
        echo "   - Send MULTIPLE SMS messages in bulk tests"
        echo "   - Use MORE credits from your account"
        echo "   - To disable bulk tests only, set ENABLE_BULK_SMS_TESTS=false in .env"
        echo
    fi
    echo "   To disable all real SMS tests, set ENABLE_REAL_SMS_TESTS=false in .env"
    echo
fi

echo "🎉 Setup complete! You can now run the tests."
echo "   Start with: php examples/test_example.php" 
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

read -p "Mobile Message Username: " username
read -s -p "Mobile Message Password: " password
echo
read -p "Test Phone Number (e.g., 61412345678): " phone
read -p "Test Sender ID (e.g., TEST): " sender

# Default sender if not provided
if [ -z "$sender" ]; then
    sender="TEST"
fi

echo
read -p "Enable real SMS sending for tests? (y/N): " enable_sms

# Set enable_sms to boolean
if [[ $enable_sms =~ ^[Yy]$ ]]; then
    enable_sms="true"
else
    enable_sms="false"
fi

# Update .env file
echo "📝 Updating .env file..."

# Use sed to replace values in .env file
sed -i.bak "s/MOBILE_MESSAGE_USERNAME=.*/MOBILE_MESSAGE_USERNAME=$username/" .env
sed -i.bak "s/MOBILE_MESSAGE_PASSWORD=.*/MOBILE_MESSAGE_PASSWORD=$password/" .env
sed -i.bak "s/TEST_PHONE_NUMBER=.*/TEST_PHONE_NUMBER=$phone/" .env
sed -i.bak "s/TEST_SENDER_ID=.*/TEST_SENDER_ID=$sender/" .env
sed -i.bak "s/ENABLE_REAL_SMS_TESTS=.*/ENABLE_REAL_SMS_TESTS=$enable_sms/" .env

# Remove backup file
rm .env.bak

echo "✅ Configuration complete!"
echo
echo "📊 Your settings:"
echo "   Username: $username"
echo "   Password: [HIDDEN]"
echo "   Test Phone: $phone"
echo "   Sender ID: $sender"
echo "   Real SMS Tests: $enable_sms"
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
    echo "   To disable real SMS tests, set ENABLE_REAL_SMS_TESTS=false in .env"
    echo
fi

echo "🎉 Setup complete! You can now run the tests."
echo "   Start with: php examples/test_example.php" 
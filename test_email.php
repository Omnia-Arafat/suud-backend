<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Mail;

// Create the application
$app = new Application(__DIR__);

// Load environment variables
$app->make('Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables')->bootstrap($app);

// Load configuration
$app->make('Illuminate\Foundation\Bootstrap\LoadConfiguration')->bootstrap($app);

// Register service providers
$app->register('Illuminate\Mail\MailServiceProvider');

// Test email sending
echo "Testing email configuration...\n";
echo "Attempting to send test email to RSL111@hotmail.com...\n\n";

try {
    Mail::raw('This is a test email from SU\'UD Platform. If you receive this, your email configuration is working correctly!', function ($mail) {
        $mail->to('RSL111@hotmail.com')
            ->subject('SU\'UD Platform - Email Test');
    });
    
    echo "✅ Email sent successfully!\n";
    echo "Check your inbox at RSL111@hotmail.com\n";
    
} catch (Exception $e) {
    echo "❌ Email failed to send:\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    if (strpos($e->getMessage(), 'authentication') !== false) {
        echo "💡 This looks like an authentication issue.\n";
        echo "Please check your MAIL_PASSWORD in the .env file.\n";
        echo "For Hotmail/Outlook, you might need an 'App Password'.\n";
    }
}

echo "\nNote: You need to replace 'YOUR_HOTMAIL_PASSWORD_HERE' in .env with your actual password.\n";

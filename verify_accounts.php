<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\Company;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== SU'UD TEST ACCOUNTS VERIFICATION ===\n\n";

// Get all users
echo "📋 USERS CREATED:\n";
echo "─────────────────────────────────────\n";
$users = User::all(['id', 'name', 'email', 'role', 'is_active']);

foreach ($users as $user) {
    $status = $user->is_active ? '✅ Active' : '❌ Inactive';
    $roleEmoji = match($user->role) {
        'admin' => '👨‍💼',
        'employee' => '👨‍💻',
        'employer' => '🏢',
        default => '👤'
    };
    
    echo sprintf(
        "%s %s (ID: %d)\n   📧 %s\n   🎭 %s\n   %s\n\n",
        $roleEmoji,
        $user->name,
        $user->id,
        $user->email,
        ucfirst($user->role),
        $status
    );
}

// Get all companies
echo "\n🏢 COMPANIES CREATED:\n";
echo "─────────────────────────────────────\n";
$companies = Company::with('user')->get(['id', 'company_name', 'user_id', 'industry']);

foreach ($companies as $company) {
    echo sprintf(
        "🏭 %s (ID: %d)\n   👤 Owner: %s\n   🏭 Industry: %s\n   📧 Contact: %s\n\n",
        $company->company_name,
        $company->id,
        $company->user->name,
        $company->industry,
        $company->user->email
    );
}

// Statistics
echo "\n📊 STATISTICS:\n";
echo "─────────────────────────────────────\n";
$stats = User::selectRaw('role, count(*) as count')->groupBy('role')->get();
foreach ($stats as $stat) {
    $emoji = match($stat->role) {
        'admin' => '👨‍💼',
        'employee' => '👨‍💻',
        'employer' => '🏢',
        default => '👤'
    };
    echo sprintf("%s %s: %d\n", $emoji, ucfirst($stat->role), $stat->count);
}

$totalCompanies = Company::count();
echo "🏭 Companies: {$totalCompanies}\n";

echo "\n✅ VERIFICATION COMPLETE!\n";
echo "All accounts are ready for testing.\n";

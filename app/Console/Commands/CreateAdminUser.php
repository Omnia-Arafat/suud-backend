<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create 
                            {--name= : The name of the admin user}
                            {--email= : The email of the admin user}
                            {--password= : The password of the admin user}
                            {--force : Force creation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user for the SU\'UD platform';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🛡️  SU\'UD Admin User Creation');
        $this->line('================================');

        // Get user input
        $name = $this->option('name') ?? $this->ask('Admin Name', 'SU\'UD Admin');
        $email = $this->option('email') ?? $this->ask('Admin Email');
        $password = $this->option('password') ?? $this->secret('Admin Password (min 8 characters)');

        // Validate input
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8'
        ]);

        if ($validator->fails()) {
            $this->error('❌ Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->line('   • ' . $error);
            }
            return 1;
        }

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error("❌ User with email {$email} already exists!");
            return 1;
        }

        // Show confirmation unless --force is used
        if (!$this->option('force')) {
            $this->line('');
            $this->line('Admin user details:');
            $this->line('Name: ' . $name);
            $this->line('Email: ' . $email);
            $this->line('Role: admin');
            $this->line('');

            if (!$this->confirm('Create this admin user?', true)) {
                $this->info('❌ Admin user creation cancelled.');
                return 0;
            }
        }

        try {
            // Create the admin user
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'admin',
                'is_active' => true,
            ]);

            $this->line('');
            $this->info('✅ Admin user created successfully!');
            $this->line('');
            $this->line('📋 Login Details:');
            $this->line('Email: ' . $user->email);
            $this->line('Password: [hidden for security]');
            $this->line('Role: ' . $user->role);
            $this->line('');
            $this->line('🌐 You can now login at: ' . config('app.url') . '/admin');
            $this->line('');
            $this->warn('⚠️  Please change the password after first login!');

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Failed to create admin user: ' . $e->getMessage());
            return 1;
        }
    }
}

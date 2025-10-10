<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

class TestAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin Account (already exists but let's ensure it's there)
        $admin = User::firstOrCreate(
            ['email' => 'admin@suud.com'],
            [
                'name' => 'SU\'UD Admin',
                'password' => Hash::make('admin123456'),
                'role' => 'admin',
                'is_active' => true,
                'specialization' => null,
                'university' => null,
                'profile_summary' => 'Platform Administrator',
                'phone' => '+966501234567',
                'location' => 'Riyadh, Saudi Arabia',
            ]
        );

        // Employee Test Account
        $employee = User::firstOrCreate(
            ['email' => 'employee@suud.com'],
            [
                'name' => 'Ahmed Al-Rashid',
                'password' => Hash::make('employee123'),
                'role' => 'employee',
                'is_active' => true,
                'specialization' => 'Software Development',
                'university' => 'King Saud University',
                'profile_summary' => 'Passionate software developer with 2 years of experience in web development. Skilled in React, Laravel, and modern web technologies. Looking for opportunities to grow and contribute to innovative projects.',
                'phone' => '+966502345678',
                'location' => 'Riyadh, Saudi Arabia',
            ]
        );

        // Employer Test Account
        $employer = User::firstOrCreate(
            ['email' => 'employer@suud.com'],
            [
                'name' => 'Sara Al-Mansouri',
                'password' => Hash::make('employer123'),
                'role' => 'employer',
                'is_active' => true,
                'specialization' => null,
                'university' => null,
                'profile_summary' => 'HR Manager at TechNova Solutions, responsible for talent acquisition and employee development.',
                'phone' => '+966503456789',
                'location' => 'Jeddah, Saudi Arabia',
            ]
        );

        // Create company profile for employer
        if ($employer->wasRecentlyCreated || !$employer->company) {
            Company::firstOrCreate(
                ['user_id' => $employer->id],
                [
                    'company_name' => 'TechNova Solutions',
                    'description' => 'Leading technology company specializing in innovative software solutions for businesses across the Middle East. We focus on digital transformation and cutting-edge technology implementations.',
                    'industry' => 'Information Technology',
                    'website' => 'https://technova.sa',
                    'location' => 'Jeddah, Saudi Arabia',
                    'company_size' => '50-100 employees',
                    'founded_year' => 2018,
                ]
            );
        }

        // Additional Employee Account
        $employee2 = User::firstOrCreate(
            ['email' => 'fatima@suud.com'],
            [
                'name' => 'Fatima Al-Zahra',
                'password' => Hash::make('fatima123'),
                'role' => 'employee',
                'is_active' => true,
                'specialization' => 'Data Science',
                'university' => 'King Abdulaziz University',
                'profile_summary' => 'Data science enthusiast with expertise in machine learning and data analysis. Graduate with honors from KAU, seeking opportunities in AI and data-driven solutions.',
                'phone' => '+966504567890',
                'location' => 'Jeddah, Saudi Arabia',
            ]
        );

        // Additional Employer Account
        $employer2 = User::firstOrCreate(
            ['email' => 'mohammed@suud.com'],
            [
                'name' => 'Mohammed Al-Otaibi',
                'password' => Hash::make('mohammed123'),
                'role' => 'employer',
                'is_active' => true,
                'specialization' => null,
                'university' => null,
                'profile_summary' => 'Senior Recruitment Specialist at Digital Horizon, focused on finding top talent for technology companies in the Kingdom.',
                'phone' => '+966505678901',
                'location' => 'Dammam, Saudi Arabia',
            ]
        );

        // Create company profile for second employer
        if ($employer2->wasRecentlyCreated || !$employer2->company) {
            Company::firstOrCreate(
                ['user_id' => $employer2->id],
                [
                    'company_name' => 'Digital Horizon',
                    'description' => 'A forward-thinking digital agency that helps businesses transform their operations through technology. We specialize in web development, mobile apps, and digital marketing.',
                    'industry' => 'Digital Services',
                    'website' => 'https://digitalhorizon.sa',
                    'location' => 'Dammam, Saudi Arabia',
                    'company_size' => '20-50 employees',
                    'founded_year' => 2020,
                ]
            );
        }

        $this->command->info('Test accounts created successfully!');
        $this->command->line('');
        $this->command->line('=== TEST ACCOUNTS CREATED ===');
        $this->command->line('');
        
        $this->command->line('👨‍💼 ADMIN ACCOUNT:');
        $this->command->line('Email: admin@suud.com');
        $this->command->line('Password: admin123456');
        $this->command->line('Role: Admin');
        $this->command->line('');
        
        $this->command->line('👨‍💻 EMPLOYEE ACCOUNTS:');
        $this->command->line('1. Email: employee@suud.com');
        $this->command->line('   Password: employee123');
        $this->command->line('   Name: Ahmed Al-Rashid');
        $this->command->line('   Specialization: Software Development');
        $this->command->line('');
        $this->command->line('2. Email: fatima@suud.com');
        $this->command->line('   Password: fatima123');
        $this->command->line('   Name: Fatima Al-Zahra');
        $this->command->line('   Specialization: Data Science');
        $this->command->line('');
        
        $this->command->line('🏢 EMPLOYER ACCOUNTS:');
        $this->command->line('1. Email: employer@suud.com');
        $this->command->line('   Password: employer123');
        $this->command->line('   Name: Sara Al-Mansouri');
        $this->command->line('   Company: TechNova Solutions');
        $this->command->line('');
        $this->command->line('2. Email: mohammed@suud.com');
        $this->command->line('   Password: mohammed123');
        $this->command->line('   Name: Mohammed Al-Otaibi');
        $this->command->line('   Company: Digital Horizon');
        $this->command->line('');
        
        $this->command->info('All accounts are active and ready for testing!');
    }
}

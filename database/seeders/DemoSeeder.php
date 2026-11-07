<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Demo accounts and office data must not be seeded in production.');
        }

        $password = config('app.demo_seed_password');

        if (! is_string($password) || $password === '') {
            throw new RuntimeException('Set DEMO_SEED_PASSWORD in your local .env before seeding demo accounts.');
        }

        $offices = [
            [
                'name' => 'Admission',
                'prefix' => 'AMS',
                'services' => ['Application Processing', 'Entrance Exam Scheduling'],
                'staff' => ['Admission Staff', 'admission.staff@example.com'],
            ],
            [
                'name' => 'Registrar',
                'prefix' => 'RGS',
                'services' => ['Enrollment', 'Transcript Request'],
                'staff' => ['Registrar Staff', 'registrar.staff@example.com'],
            ],
            [
                'name' => 'Accounting',
                'prefix' => 'ACC',
                'services' => ['Scholarship Processing', 'Tuition Payment'],
                'staff' => ['Accounting Staff', 'accounting.staff@example.com'],
            ],
        ];

        foreach ($offices as $office) {
            $department = Department::updateOrCreate(
                ['prefix' => $office['prefix']],
                ['name' => $office['name'], 'is_active' => true],
            );

            foreach ($office['services'] as $serviceName) {
                Service::updateOrCreate(
                    ['department_id' => $department->id, 'name' => $serviceName],
                    ['is_active' => true],
                );
            }

            User::updateOrCreate(
                ['email' => $office['staff'][1]],
                [
                    'full_name' => $office['staff'][0],
                    'password' => $password,
                    'role' => 'staff',
                    'department_id' => $department->id,
                    'is_active' => true,
                    'is_demo' => true,
                    'email_verified_at' => now(),
                ],
            );
        }

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'full_name' => 'Demo Administrator',
                'password' => $password,
                'role' => 'admin',
                'is_active' => true,
                'is_demo' => true,
                'email_verified_at' => now(),
            ],
        );

        $students = [
            ['Student One', 'student001@example.com', 'DEMO-2026-001', 'BS Information Technology'],
            ['Student Two', 'student002@example.com', 'DEMO-2026-002', 'BS Accountancy'],
            ['Student Three', 'student003@example.com', 'DEMO-2026-003', 'BS Computer Science'],
        ];

        foreach ($students as [$name, $email, $studentId, $course]) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'full_name' => $name,
                    'password' => $password,
                    'role' => 'student',
                    'student_id' => $studentId,
                    'course' => $course,
                    'is_active' => true,
                    'is_demo' => true,
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}

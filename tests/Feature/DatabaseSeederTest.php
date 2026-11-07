<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_fake_offices_and_demo_accounts_idempotently(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('departments', 3);
        $this->assertDatabaseCount('services', 6);
        $this->assertDatabaseCount('users', 7);
        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'registrar.staff@example.com',
            'role' => 'staff',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'student001@example.com',
            'student_id' => 'DEMO-2026-001',
            'role' => 'student',
        ]);

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->assertTrue(Hash::check(config('app.demo_seed_password'), $admin->password));
    }

    public function test_demo_seeder_refuses_to_run_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->expectException(RuntimeException::class);

        app(DemoSeeder::class)->run();
    }
}

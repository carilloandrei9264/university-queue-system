<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateFirstAdmin extends Command
{
    protected $signature = 'queue-it:create-admin';

    protected $description = 'Create the first administrator from the server terminal';

    public function handle(ActivityLogger $activityLogger): int
    {
        if (User::where('role', 'admin')->exists()) {
            $this->components->error('An administrator already exists. This command only creates the first administrator.');

            return self::FAILURE;
        }

        $fullNameAnswer = $this->ask('Administrator full name', 'System Administrator');
        $emailAnswer = $this->ask('Administrator email');
        $fullName = is_string($fullNameAnswer) ? trim($fullNameAnswer) : '';
        $email = is_string($emailAnswer) ? mb_strtolower(trim($emailAnswer)) : '';
        $password = $this->secret('Password (minimum 12 characters)');
        $passwordConfirmation = $this->secret('Confirm password');

        $validator = Validator::make([
            'full_name' => $fullName,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($fullName, $email, $password, $activityLogger): void {
            $admin = User::create([
                'full_name' => $fullName,
                'email' => $email,
                'password' => $password,
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $activityLogger->record(null, 'admin.created', 'user', $admin->id);
        });

        $this->components->info("Administrator account created for {$email}.");

        return self::SUCCESS;
    }
}

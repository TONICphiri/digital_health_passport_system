<?php

namespace App\Console\Commands;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Creates the first System Administrator account, or resets the
 * password of an existing one. The password is typed here and is
 * never written to a file, an environment variable or the source
 * control. Run it from a computer that can reach the database:
 *
 *     php artisan admin:create
 */
class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create
        {--name= : Full name of the administrator}
        {--email= : Email address of the administrator}
        {--password= : Password, omit it to type it hidden}';

    protected $description = 'Create or update the System Administrator account';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Full name', 'System Administrator');
        $email = $this->option('email') ?: $this->ask('Email address');

        $existing = User::query()->where('email', $email)->first();

        if ($existing && ! $this->confirm("An account for {$email} already exists. Set a new password for it?", false)) {
            $this->info('Nothing was changed.');

            return self::FAILURE;
        }

        $password = $this->option('password');

        if (! $password) {
            $password = $this->secret('Password');

            if ($password !== $this->secret('Confirm password')) {
                $this->error('The passwords do not match.');

                return self::FAILURE;
            }
        }

        $validator = validator([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $existing ? Rule::unique('users')->ignore($existing) : 'unique:users'],
            'password' => ['required', PasswordRule::min(8)->letters()->numbers()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if ($existing) {
            $existing->forceFill([
                'name' => $name,
                'password' => $password,
                'must_change_password' => false,
            ])->save();

            $user = $existing;
        } else {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'job_title' => 'System Administrator',
                'status' => UserStatus::Active,
                'must_change_password' => false,
                'password' => $password,
            ]);
        }

        $user->syncRoles([RoleName::SystemAdmin->value]);

        $this->info("The System Administrator account for {$email} is ready.");

        return self::SUCCESS;
    }
}

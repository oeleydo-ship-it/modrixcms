<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class MakeAdminCommand extends Command
{
    protected $signature = 'site:admin {email : Email to sign in with} {--name=Admin : Display name}';

    protected $description = 'Create an admin account, or reset the password of an existing one';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $password = (string) $this->secret('Password (min. 10 characters)');

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => ['required', 'email'], 'password' => ['required', Password::min(10)]],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $email]);
        $user->name = $user->exists ? $user->name : (string) $this->option('name');
        $user->password = $password;
        $user->is_admin = true;
        $user->save();

        $this->info(($user->wasRecentlyCreated ? 'Created' : 'Updated')." admin {$email}. Sign in at ".route('admin.login'));

        return self::SUCCESS;
    }
}

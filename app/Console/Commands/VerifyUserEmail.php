<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

#[Signature('app:verify-user-email {email? : The email address of the user to verify} {--list : List all users and their verification status}')]
#[Description('Verify a user\'s email address (local development only)')]
class VerifyUserEmail extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Only allow this command to run in local development environment
        if (!App::environment('local')) {
            $this->error('This command can only be run in local development environment.');
            return Command::FAILURE;
        }

        // If list option is provided, show all users
        if ($this->option('list')) {
            $users = User::all();
            $this->table(
                ['ID', 'Name', 'Email', 'Verified', 'Created At'],
                $users->map(function ($user) {
                    return [
                        $user->id,
                        $user->name,
                        $user->email,
                        $user->hasVerifiedEmail() ? 'Yes' : 'No',
                        $user->created_at->format('Y-m-d H:i:s'),
                    ];
                })->toArray()
            );
            return Command::SUCCESS;
        }

        $email = $this->argument('email');

        if (!$email) {
            // Get all unverified users
            $users = User::whereNull('email_verified_at')->get();

            if ($users->isEmpty()) {
                $this->info('No unverified users found.');
                return Command::SUCCESS;
            }

            $choices = $users->pluck('email', 'id')->toArray();
            $userId = $this->choice('Select a user to verify', $choices);
            $user = $users->firstWhere('id', $userId);
        } else {
            $user = User::where('email', $email)->first();

            if (!$user) {
                $this->error("User with email {$email} not found.");
                return Command::FAILURE;
            }
        }

        if ($user->hasVerifiedEmail()) {
            $this->info("User {$user->email} is already verified.");
            return Command::SUCCESS;
        }

        // Verify the user's email
        $user->markEmailAsVerified();

        $this->info("Successfully verified email for user: {$user->email}");

        return Command::SUCCESS;
    }
}
<?php

namespace App\Console\Commands;

use App\Models\Operator;
use App\Models\User;
use Illuminate\Console\Command;

class CleanupOperators extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'operators:cleanup {email? : Delete a single user + operator by email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete pending/rejected operators and their users, or a single user+operator by email';

    public function handle(): int
    {
        $email = $this->argument('email');

        if ($email !== null && $email !== '') {
            $user = User::where('email', $email)->first();
            if (!$user) {
                $this->warn("No user found with email: {$email}");
                return self::FAILURE;
            }
            Operator::where('user_id', $user->id)->delete();
            $user->delete();
            $this->info("Deleted user + operator for: {$email}");
            return self::SUCCESS;
        }

        $deleted = 0;
        Operator::with('user')->whereIn('status', ['pending', 'rejected'])->chunk(50, function ($operators) use (&$deleted) {
            foreach ($operators as $op) {
                if ($op->user && $op->user->role === 'operator') {
                    $op->user->delete();
                }
                $op->delete();
                $deleted++;
            }
        });

        $this->info("Deleted {$deleted} pending/rejected operators and their users.");

        return self::SUCCESS;
    }
}

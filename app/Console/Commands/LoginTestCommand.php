<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\CoreModule\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

#[Signature('app:login-test-command')]
#[Description('Test authentication and route access for communications module')]
class LoginTestCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing authentication and route access...');

        // Find admin user
        $adminUser = User::where('email', 'yhernandez@css.gob.pa')->first();

        if (! $adminUser) {
            $this->error('Admin user not found');

            return 1;
        }

        $this->info("Found admin user: {$adminUser->name} ({$adminUser->email})");
        $this->info('Email verified: '.($adminUser->email_verified_at ? 'Yes' : 'No'));
        $this->info('Has communications.manage permission: '.($adminUser->hasPermissionTo('communications.manage') ? 'Yes' : 'No'));

        // Test authentication
        Auth::login($adminUser);
        $this->info('User authenticated: '.(Auth::check() ? 'Yes' : 'No'));

        if (Auth::check()) {
            $this->info('Authenticated user: '.Auth::user()->name);
            $this->info('User ID: '.Auth::id());
        }

        // Test route access
        $this->info("\nTesting route access...");

        try {
            // Test categories route
            $this->call('route:list', ['--name=communications.admin.categories.index']);
            $this->info('Categories route exists');

            // Test tags route
            $this->call('route:list', ['--name=communications.admin.tags.index']);
            $this->info('Tags route exists');

            // Test news route
            $this->call('route:list', ['--name=communications.news.index']);
            $this->info('News route exists');

            // Test moderation route
            $this->call('route:list', ['--name=communications.moderation.index']);
            $this->info('Moderation route exists');

        } catch (\Exception $e) {
            $this->error('Error testing routes: '.$e->getMessage());
        }

        Auth::logout();
        $this->info('User logged out');

        return 0;
    }
}

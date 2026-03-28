<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\ConsoleOutput;

class RefreshDatabaseCommand extends Command
{
    protected $signature = 'g';
    protected $description = 'Refresh database, run migrations, and seed data with full logs (stop on failure)';

    protected ConsoleOutput $outputHandler;

    public function __construct()
    {
        parent::__construct();
        $this->outputHandler = new ConsoleOutput();
    }

    public function handle(): void
    {
        // Confirm reset
        if (!$this->confirm('⚠️ This will DROP all tables and reset the database! Continue?', true)) {
            $this->info('Operation canceled.');
            return;
        }

        // -----------------------------
        // Step 1: Migrate fresh
        // -----------------------------
        $this->info("\n🔄 Running fresh migrations...\n");

        try {
            Artisan::call('migrate:fresh', ['--force' => true], $this->outputHandler);
            $this->info("\n✅ Migrations completed successfully.\n");
        } catch (\Exception $e) {
            $this->error("\n❌ Migration failed: ".$e->getMessage());
            return; // stop operation
        }

        // -----------------------------
        // Step 2: Seed database
        // -----------------------------
        $this->info("🌱 Running database seeders...");

        // List of seeders (adjust as needed)
        $seeders = [
            'OrganizationSeeder',
            'UserSeeder',
            'RoleSeeder',
            'OrganizationUserSeeder',
            'UnitSeeder',
            'CurrencySeeder',
        ];

        foreach ($seeders as $seeder) {
            try {
                Artisan::call('db:seed', ['--class' => $seeder, '--force' => true], $this->outputHandler);
                $this->info("✅ {$seeder} seeded successfully.");
            } catch (\Exception $e) {
                $this->error("❌ Seeder {$seeder} failed: ".$e->getMessage()."\n");
                return; // stop operation immediately
            }
        }

        $this->line("
██████╗   ██████╗  ███╗   ███╗ ███████╗ ████████╗ ███████╗  ██╗ ██╗   ██╗
██╔══██╗ ██╔═══██╗ ████╗ ████║ ██╔════╝ ╚══██╔══╝ ██╔═══██║ ██║ ╚██╗ ██╔╝
██║  ██║ ██║   ██║ ██╔████╔██║ █████╗      ██║    ██████╔═╝ ██║  ╚═██╔═╝
██║  ██║ ██║   ██║ ██║╚██╔╝██║ ██╔══╝      ██║    ██╔═══██╗ ██║  ██╔╚██╗
██████╔╝ ╚██████╔╝ ██║ ╚═╝ ██║ ███████╗    ██║    ██║   ██║ ██║ ██╔╝ ╚═██╗
╚═════╝   ╚═════╝  ╚═╝     ╚═╝ ╚══════╝    ╚═╝    ╚═╝   ╚═╝ ╚═╝ ╚═╝    ╚═╝
");
    }
}

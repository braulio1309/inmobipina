<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE operations MODIFY `type` VARCHAR(50) NOT NULL');
        }
    }

    public function down(): void
    {
        // Keep the column as a string so rollback cannot truncate existing operation types.
    }
};

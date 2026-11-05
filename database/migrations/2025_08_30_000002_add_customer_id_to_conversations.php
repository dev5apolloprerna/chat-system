<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('conversations', function (Blueprint $t) {
            $t->foreignId('customer_id')->nullable()->after('created_by')
              ->constrained('customers')->nullOnDelete();
            // (Optional) keep title as the E.164 number for readability; or drop later.
            $t->index('customer_id');
        });
    }
    public function down(): void {
        Schema::table('conversations', function (Blueprint $t) {
            $t->dropConstrainedForeignId('customer_id');
        });
    }
};

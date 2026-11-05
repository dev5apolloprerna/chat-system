<?php
// database/migrations/2025_08_30_120000_add_last_inbound_at_to_customers.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('customers', function (Blueprint $t) {
      $t->timestamp('last_inbound_at')->nullable()->after('meta');
    });
  }
  public function down(): void {
    Schema::table('customers', function (Blueprint $t) {
      $t->dropColumn('last_inbound_at');
    });
  }
};

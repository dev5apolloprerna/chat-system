<?php
// database/migrations/2025_08_30_000003_create_message_customer_receipts_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('message_customer_receipts', function (Blueprint $t) {
            $t->id();
            $t->char('message_id', 26);  // ULID from messages.id
            $t->foreign('message_id')->references('id')->on('messages')->cascadeOnDelete();
            $t->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $t->enum('status', ['sent','delivered','seen'])->default('sent');
            $t->timestamp('delivered_at')->nullable();
            $t->timestamp('seen_at')->nullable();
            $t->timestamps();
            $t->unique(['message_id','customer_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('message_customer_receipts'); }
};

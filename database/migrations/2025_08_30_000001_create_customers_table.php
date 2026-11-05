<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('wa_number', 32)->unique();   // E164 digits only (e.g., 91987...)
            $t->string('display_name')->nullable();  // Optional “profile name”
            $t->string('name')->nullable();          // Your CRM name (if you enrich later)
            $t->json('meta')->nullable();            // Anything else
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('customers'); }
};

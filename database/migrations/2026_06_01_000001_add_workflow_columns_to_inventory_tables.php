<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_sessions', function (Blueprint $table) {
            $table->text('decision')->nullable()->after('status'); // قرار رئيس القسم بشأن الفروقات
            $table->timestamp('resolved_at')->nullable()->after('decision');
        });

        Schema::table('inventory_session_items', function (Blueprint $table) {
            // null = لم تُجرد المادة بعد
            $table->integer('actual_quantity')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_sessions', function (Blueprint $table) {
            $table->dropColumn(['decision', 'resolved_at']);
        });
    }
};

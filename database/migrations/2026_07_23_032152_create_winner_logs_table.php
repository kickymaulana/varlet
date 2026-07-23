<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('winner_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prize_id')->constrained()->onDelete('cascade');
            $table->foreignId('participant_id')->constrained()->onDelete('cascade');
            $table->string('nomor_kupon');
            $table->string('nama_pemenang');
            $table->string('departemen')->nullable();
            $table->string('lokasi_kerja')->nullable();
            $table->timestamp('drawn_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('winner_logs');
    }
};

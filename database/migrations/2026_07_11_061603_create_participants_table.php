<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_induk')->unique(); // NIK Karyawan
            $table->string('nama_lengkap');
            $table->string('lokasi_kerja');
            $table->string('departemen');
            $table->string('nomor_hp')->nullable();
            $table->string('nomor_kupon')->unique()->nullable(); // Kupon Lucky Draw
            $table->boolean('is_present')->default(false); // Status Check-in
            $table->timestamp('attended_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('cliente')->change();
        });

        DB::table('users')->where('role', 'admin')->update(['role' => 'administrador']);
        DB::table('users')->where('role', 'personal')->update(['role' => 'cajero']);

        if (! DB::table('users')->where('email', 'cocina@donaross.com')->exists()) {
            DB::table('users')->insert([
                'name' => 'Cocina Doña Ross',
                'email' => 'cocina@donaross.com',
                'password' => Hash::make('cocina123'),
                'role' => 'cocina',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['administrador', 'cajero', 'cocina', 'cliente'])
                ->default('cliente')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('cliente')->change();
        });

        DB::table('users')->where('role', 'administrador')->update(['role' => 'admin']);
        DB::table('users')->where('role', 'cajero')->update(['role' => 'personal']);

        DB::table('users')->where('email', 'cocina@donaross.com')->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'personal', 'cliente'])->default('cliente')->change();
        });
    }
};
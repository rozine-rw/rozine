<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['permissions', 'roles'] as $name) {
            Schema::create($name, function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }
        foreach (['permissions' => 'permission_id', 'roles' => 'role_id'] as $name => $key) {
            Schema::create('model_has_'.$name, function (Blueprint $table) use ($name, $key): void {
                $table->foreignId($key)->constrained($name)->cascadeOnDelete();
                $table->string('model_type');
                $table->foreignId('model_id')->constrained('staff_accounts', 'user_id')->restrictOnDelete();
                $table->index(['model_id', 'model_type']);
                $table->primary([$key, 'model_id', 'model_type']);
            });
            DB::statement("ALTER TABLE model_has_{$name} ADD CONSTRAINT staff_model_type CHECK (model_type = 'App\\Models\\StaffAccount')");
        }
        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });
    }

    public function down(): void
    {
        foreach (['role_has_permissions', 'model_has_roles', 'model_has_permissions', 'roles', 'permissions'] as $name) {
            Schema::dropIfExists($name);
        }
    }
};

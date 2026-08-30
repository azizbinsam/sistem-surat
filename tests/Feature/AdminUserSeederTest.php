<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_bikin_akun_admin_dari_config(): void
    {
        Config::set('admin.name', 'Admin Test');
        Config::set('admin.email', 'admin-test@example.com');
        Config::set('admin.password', 'rahasia123');

        $this->seed(AdminUserSeeder::class);

        $admin = User::where('email', 'admin-test@example.com')->first();

        $this->assertNotNull($admin);
        $this->assertSame('admin', $admin->role);
        $this->assertSame('Admin Test', $admin->name);
        $this->assertNull($admin->sekolah_id);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('rahasia123', $admin->password));
    }

    public function test_dijalankan_ulang_tidak_bikin_akun_duplikat_dan_sync_password_baru(): void
    {
        Config::set('admin.name', 'Admin Test');
        Config::set('admin.email', 'admin-test@example.com');
        Config::set('admin.password', 'password-lama');
        $this->seed(AdminUserSeeder::class);

        Config::set('admin.password', 'password-baru');
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(1, User::where('email', 'admin-test@example.com')->count());

        $admin = User::where('email', 'admin-test@example.com')->first();
        $this->assertTrue(Hash::check('password-baru', $admin->password));
    }

    public function test_tidak_bikin_akun_kalau_env_belum_diisi(): void
    {
        Config::set('admin.email', null);
        Config::set('admin.password', null);

        $this->seed(AdminUserSeeder::class);

        $this->assertSame(0, User::where('role', 'admin')->count());
    }
}
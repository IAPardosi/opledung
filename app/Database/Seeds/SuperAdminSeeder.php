<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Models\UserModel;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;

/**
 * Membuat akun Super Admin pertama.
 *
 * Email dan password diambil dari .env (superadmin.email, superadmin.password).
 * Bila password kosong, dibuat acak dan ditampilkan sekali di terminal.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $users = new UserModel();
        $email = env('superadmin.email', 'superadmin@silsilah.local');

        if ($users->findByCredentials(['email' => $email]) !== null) {
            return;
        }

        $password = env('superadmin.password') ?: bin2hex(random_bytes(8));

        $user = new User([
            'username' => 'superadmin',
            'email'    => $email,
            'password' => $password,
        ]);
        $users->save($user);

        $user = $users->findById($users->getInsertID());
        $user->activate();
        $user->syncGroups('superadmin');

        if (is_cli() && ! env('superadmin.password')) {
            echo "  Super Admin dibuat: {$email} / {$password}" . PHP_EOL;
            echo '  Segera ganti password ini setelah login pertama.' . PHP_EOL;
        }
    }
}

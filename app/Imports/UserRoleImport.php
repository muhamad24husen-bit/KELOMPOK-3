<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Spatie\Permission\Models\Role;

class UserRoleImport implements ToCollection, SkipsEmptyRows
{
    public int $created = 0;
    public int $updated = 0;
    public int $skipped = 0;
    public array $errors = [];
    public string $defaultPassword;

    public function __construct()
    {
        // Default password untuk semua user baru yang di-import
        $this->defaultPassword = 'password123';
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            // Skip rows that look like a header (not data)
            $firstCell = strtolower(trim((string) ($row[0] ?? '')));

            if (in_array($firstCell, ['email', 'e-mail', 'mail', 'user_email', 'no', 'name', 'nama'])) {
                continue;
            }

            $rowNumber = $index + 1;

            // Column A (index 0) = name, Column B (index 1) = email, Column C (index 2) = role
            $name     = trim((string) ($row[0] ?? ''));
            $email    = trim((string) ($row[1] ?? ''));
            $roleName = strtolower(trim((string) ($row[2] ?? '')));

            // Validate: name is required
            if (empty($name)) {
                $this->errors[] = "Baris {$rowNumber}: Kolom 'name' kosong.";
                $this->skipped++;
                continue;
            }

            // Validate: email is required
            if (empty($email)) {
                $this->errors[] = "Baris {$rowNumber}: Kolom 'email' kosong.";
                $this->skipped++;
                continue;
            }

            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->errors[] = "Baris {$rowNumber}: Format email '{$email}' tidak valid.";
                $this->skipped++;
                continue;
            }

            // Validate: role is required
            if (empty($roleName)) {
                $this->errors[] = "Baris {$rowNumber}: Kolom 'role' kosong.";
                $this->skipped++;
                continue;
            }

            // Check if role exists in Spatie
            if (!Role::where('name', $roleName)->exists()) {
                $availableRoles = Role::pluck('name')->implode(', ');
                $this->errors[] = "Baris {$rowNumber}: Role '{$roleName}' tidak valid. Role tersedia: {$availableRoles}";
                $this->skipped++;
                continue;
            }

            // Check if user already exists
            $existingUser = User::where('email', $email)->first();

            if ($existingUser) {
                // Proteksi: jangan ubah role super_admin lewat import
                if ($existingUser->hasRole('super_admin')) {
                    $this->errors[] = "Baris {$rowNumber}: User '{$email}' adalah Super Admin, tidak bisa diubah via import.";
                    $this->skipped++;
                    continue;
                }

                // User sudah ada -> update role-nya
                $existingUser->syncRoles([$roleName]);
                $existingUser->update(['role' => $roleName, 'name' => $name]);
                $this->updated++;
            } else {
                // User belum ada -> buat user baru
                $user = User::create([
                    'name'     => $name,
                    'email'    => $email,
                    'password' => Hash::make($this->defaultPassword),
                    'role'     => $roleName,
                ]);

                $user->assignRole($roleName);
                $this->created++;
            }
        }
    }
}

<x-mail::message>
# Selamat Datang, {{ $user->name }}!

Akun Anda di **BEMS Dashboard** telah berhasil dibuat oleh administrator sistem.

## Informasi Login

<x-mail::panel>
**Email:** {{ $user->email }}

**Password:** `{{ $password }}`

**Role:** {{ ucfirst(str_replace('_', ' ', $user->getRoleNames()->first() ?? 'user')) }}
</x-mail::panel>

> **Penting:** Segera ganti password Anda setelah login pertama kali demi keamanan akun.

<x-mail::button :url="$loginUrl" color="primary">
Login Sekarang
</x-mail::button>

Jika Anda merasa tidak meminta akun ini atau ada pertanyaan, silakan hubungi administrator sistem.

Terima kasih,<br>
**Tim BEMS Dashboard**
</x-mail::message>

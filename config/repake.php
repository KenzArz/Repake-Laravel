<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin RePake
    |--------------------------------------------------------------------------
    |
    | Daftar email yang secara eksplisit diberi akses ke /admin/dashboard.
    | Akses admin TIDAK ditentukan dari domain email (staff/student), karena
    | proyek ini dikelola oleh mahasiswa. Tambahkan lebih dari satu email
    | dengan memisahkannya memakai koma pada environment ADMIN_EMAILS.
    |
    */

    'admin_emails' => array_values(array_filter(array_map(
        fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('ADMIN_EMAILS', '2410631170004@student.unsika.ac.id'))
    ))),

];

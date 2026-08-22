<?php

return [
    'uploaded' => 'Foto gagal diunggah. Biasanya file terlalu besar untuk HP. Coba lagi, foto akan dikompres otomatis.',
    'mimes' => ':attribute harus berupa gambar (jpg, png, webp, heic).',
    'max' => [
        'file' => ':attribute maksimal :max KB.',
        'string' => ':attribute terlalu panjang.',
        'numeric' => ':attribute terlalu besar.',
        'array' => ':attribute terlalu banyak item.',
    ],
    'required' => ':attribute wajib diisi.',
    'attributes' => [
        'photo_front' => 'Foto depan',
        'photo_back' => 'Foto belakang',
    ],
];

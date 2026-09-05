<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePeminjamanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alkes_id' => 'required|exists:alkes,id',
            'ruangan_peminjam_id' => 'required|exists:ruangan,id',
            'peminjam_nama' => 'required|string|max:255',
            'tanggal_pinjam' => 'required|date',
            'estimasi_kembali' => 'required|date|after_or_equal:tanggal_pinjam',
            'keterangan' => 'nullable|string',
        ];
    }
}

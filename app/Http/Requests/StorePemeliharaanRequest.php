<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePemeliharaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alkes_id' => 'required|exists:alkes,id',
            'jenis_tindakan' => 'required|string',
            'tanggal_lapor' => 'nullable|string',
            'tanggal_mulai' => 'nullable|string',
            'gejala_kerusakan' => 'nullable|string',
            'deskripsi_kerusakan' => 'nullable|string',
            'pelaksana_vendor' => 'nullable|string',
            'tindakan_perbaikan' => 'nullable|string',
            'biaya' => 'nullable|numeric',
            'foto_kerusakan' => 'nullable|image|max:5120',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMutasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alkes_id' => 'required|exists:alkes,id',
            'ruangan_tujuan_id' => 'required|exists:ruangan,id',
            'pemohon' => 'required|string|max:255',
            'penanggung_jawab' => 'required|string|max:255',
            'alasan_mutasi' => 'required|string',
        ];
    }
}

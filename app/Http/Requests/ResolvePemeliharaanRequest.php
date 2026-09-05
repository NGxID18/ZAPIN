<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolvePemeliharaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'diagnosa_kerusakan' => 'required|string',
            'tindakan_perbaikan' => 'required|string',
            'pelaksana_vendor' => 'nullable|string',
            'biaya' => 'nullable|numeric',
        ];
    }
}

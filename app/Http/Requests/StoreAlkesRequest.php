<?php

namespace App\Http\Requests;

use App\Enums\KondisiAlkes;
use App\Enums\StatusAlkes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreAlkesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama_barang' => 'required|string|max:255',
            'kode_inventaris' => 'nullable|string|max:100|unique:alkes,kode_inventaris',
            'nomor_seri' => 'nullable|string|max:100',
            'nomenklatur_id' => 'nullable|exists:nomenklatur,id',
            'merk' => 'nullable|string|max:255',
            'tipe' => 'nullable|string|max:255',
            'tahun_pengadaan' => 'nullable|string|max:20',
            'jumlah' => 'nullable|integer|min:1',
            'ruangan_id' => 'required|exists:ruangan,id',
            'status' => ['required', new Enum(StatusAlkes::class)],
            'kondisi' => ['required', new Enum(KondisiAlkes::class)],
            'aspak_status' => 'nullable|string|max:50',
            'kib_status' => 'nullable|boolean',
            'keterangan' => 'nullable|string|max:1000',
        ];
    }
}

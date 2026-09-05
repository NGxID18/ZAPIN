<!DOCTYPE html>
<html>
<head>
    <title>Peringatan Kalibrasi Alat Kesehatan</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-w: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
        <h2 style="color: #d97706;">ZAPIN - Early Warning System (EWS)</h2>
        <p>Yth. Kepala Instalasi Elektromedis / Admin ASPAK,</p>
        
        <p>Melalui email ini, sistem ZAPIN menginformasikan bahwa terdapat Alat Kesehatan yang masa berlaku kalibrasinya akan segera habis dalam <strong>{{ $hariTersisa }} hari ke depan</strong>.</p>
        
        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold; width: 150px;">Nama Alat</td>
                <td style="padding: 10px; border: 1px solid #ddd;">{{ $alkes->nama_barang }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Merk / Tipe</td>
                <td style="padding: 10px; border: 1px solid #ddd;">{{ $alkes->merk ?? '-' }} / {{ $alkes->tipe ?? '-' }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Nomor Seri</td>
                <td style="padding: 10px; border: 1px solid #ddd;">{{ $alkes->nomor_seri ?? '-' }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Lokasi Ruangan</td>
                <td style="padding: 10px; border: 1px solid #ddd;">{{ $alkes->ruangan->nama_ruangan ?? '-' }}</td>
            </tr>
            <tr>
                <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold; color: #dc2626;">Batas Kalibrasi</td>
                <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold; color: #dc2626;">{{ \Carbon\Carbon::parse($alkes->tanggal_kalibrasi_berikutnya)->format('d F Y') }}</td>
            </tr>
        </table>

        <p>Sesuai dengan SPO Pemeliharaan Preventif dan Kalibrasi Berbasis EWS, mohon segera tindak lanjuti informasi ini dengan mempersiapkan jadwal kunjungan BPFK/Vendor Kalibrasi untuk menghindari <em>overdue</em>.</p>
        
        <p>Terima kasih,<br><strong>Sistem ZAPIN RSJKO Engku Haji Daud</strong></p>
    </div>
</body>
</html>

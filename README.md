# Dokumentasi Sistem Informasi Manajemen Alat Kesehatan (ZAPIN)

## 1. Pendahuluan
Sistem Informasi Manajemen Alat Kesehatan (ZAPIN) adalah aplikasi berbasis web yang dikembangkan untuk mengelola seluruh siklus hidup peralatan medis di lingkungan institusi kesehatan. Sistem ini dirancang untuk mendigitalisasi proses inventarisasi, perpindahan barang, peminjaman, pemeliharaan, hingga pemantauan jadwal kalibrasi alat.

Sebagai sebuah solusi perangkat lunak, fokus pengembangan sistem ini bukan hanya pada digitalisasi formulir kertas, melainkan penyediaan pusat data yang akurat guna mendukung kelancaran operasional tenaga medis dan mempermudah manajemen dalam pengambilan keputusan.

## 2. Modul Utama dan Nilai Guna
Berikut adalah penjabaran fungsionalitas inti dari sistem beserta dampak langsungnya terhadap efisiensi operasional rumah sakit:

### 2.1. Manajemen Inventaris Alat Kesehatan (Master Data)
- **Fungsi Sistem:** Pencatatan dan pengelolaan data spesifikasi alat kesehatan secara terpusat, mencakup informasi nomenklatur, nomor seri, hingga tahun pengadaan.
- **Manfaat Operasional:** Mempermudah pencarian rekam jejak dan validasi jumlah fisik aset yang tersedia di setiap ruangan. Digitalisasi ini memangkas waktu penelusuran dokumen fisik secara signifikan dan meminimalisasi risiko kehilangan aset akibat kelalaian pencatatan manual.

### 2.2. Sistem Mutasi Alat
- **Fungsi Sistem:** Pencatatan perpindahan lokasi fisik alat kesehatan antar ruangan atau instalasi, lengkap dengan rincian penanggung jawab dan alasan mutasi.
- **Manfaat Operasional:** Memberikan transparansi penuh terhadap rantai pergerakan aset. Manajemen dapat melacak riwayat lokasi terakhir sebuah alat secara historis, sehingga pertanggungjawaban dan pengawasan aset inventaris menjadi lebih jelas dan terukur.

### 2.3. Manajemen Peminjaman
- **Fungsi Sistem:** Modul pencatatan sirkulasi pinjam-meminjam alat antar unit yang dilengkapi dengan validasi ketersediaan dan sistem penguncian data (concurrency lock).
- **Manfaat Operasional:** Mencegah terjadinya bentrokan penjadwalan pemakaian alat (double-booking). Tenaga medis memiliki kepastian mengenai status kesiapan alat, yang pada akhirnya berdampak langsung pada kecepatan dan kelancaran pelayanan pasien pada situasi kritis.

### 2.4. Pencatatan Pemeliharaan dan Pelaporan Kerusakan
- **Fungsi Sistem:** Modul pelaporan insiden kerusakan yang mengintegrasikan bukti visual (foto) dengan pelacakan tahapan perbaikan (status tiket).
- **Manfaat Operasional:** Memangkas birokrasi pelaporan manual antar ruangan. Teknisi elektromedis dapat segera merespons kendala berdasarkan tingkat urgensi. Setiap laporan perbaikan yang selesai akan tersimpan sebagai data historis, yang sangat krusial sebagai dasar pertimbangan kelayakan operasi atau pengadaan unit pengganti di masa mendatang.

### 2.5. Sistem Peringatan Dini (Early Warning System) Kalibrasi
- **Fungsi Sistem:** Otomatisasi notifikasi peringatan berbasis latar belakang (cron job) yang mendistribusikan peringatan via email dan dasbor pada H-30 dan H-7 sebelum masa kalibrasi alat berakhir.
- **Manfaat Operasional:** Berfungsi sebagai mekanisme pemeliharaan preventif (preventive maintenance). Rumah sakit terhindar dari risiko terlewatnya jadwal kalibrasi, sehingga standar keselamatan pasien tetap terjaga serta mendukung pemenuhan indikator akreditasi mutu institusi kesehatan.

### 2.6. Dasbor Analitik dan Pelaporan
- **Fungsi Sistem:** Visualisasi data real-time menggunakan agregasi matrik untuk merangkum kondisi alat (baik, rusak) dan sebaran unit di seluruh instalasi.
- **Manfaat Operasional:** Menyediakan landasan data (data-driven) yang cepat dan presisi bagi jajaran pimpinan. Manajer dan direksi dapat langsung mengevaluasi efisiensi penggunaan alat serta menyusun perencanaan alokasi anggaran belanja modal secara lebih strategis.

## 3. Spesifikasi Arsitektur dan Keamanan
Dari sisi rekayasa perangkat lunak (software engineering), sistem ini dibangun dengan memperhatikan keandalan dan keamanan akses data:
- **Kerangka Kerja:** Dibangun menggunakan framework Laravel (PHP) dengan arsitektur pola Model-View-Controller (MVC) untuk menjaga keteraturan dan skalabilitas kode.
- **Integritas Data:** Seluruh operasi yang mengubah struktur kepemilikan dan relasi data dijalankan dalam ruang lingkup transaksi basis data (Database Transactions) untuk menghindari data yang tidak konsisten saat terjadi gangguan.
- **Isolasi Akses (Role-Based Filtering):** Terdapat pembatasan hak akses dan visibilitas data berbasis peran dan unit kerja pengguna. Sistem secara otomatis menyaring kueri (local scope) agar petugas hanya dapat memodifikasi dan mengakses data peralatan yang berada dalam rentang kewenangan instalasinya masing-masing.
- **Penanganan Kondisi Balapan (Race Condition):** Implementasi metode Pessimistic Locking pada proses sirkulasi alat diterapkan untuk memastikan konsistensi antrean saat terdapat lebih dari satu instruksi akses pada pecahan waktu yang bersamaan.

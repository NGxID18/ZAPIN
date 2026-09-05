/**
 * Daftar Resmi Unit Instalasi & Ruangan RSJKO Engku Haji Daud untuk Dropdown
 */
const RUANGAN_LIST_DROPDOWN = [
  "CSSD",
  "Fisioterapi",
  "Hemodialisa",
  "ICU",
  "IGD",
  "IGD JIWA",
  "IPSRS",
  "Irna Anak",
  "Irna Bedah",
  "Irna Penyakit Dalam",
  "Irna Perinatology",
  "Laboratorium",
  "MCU",
  "Nursestation LT4",
  "OK",
  "Poli",
  "Poli Anak",
  "Poli Bedah",
  "Poli Gigi",
  "Poli Jantung",
  "Poli Kebidanan",
  "Poli Mata",
  "Poli Penyakit Dalam",
  "Radiologi",
  "Ruang Isolasi",
  "THT",
  "UTD",
  "VK"
];

const KONDISI_LIST_DROPDOWN = [
  "Baik",
  "Rusak Ringan",
  "Rusak Berat"
];

const KALIBRASI_LIST_DROPDOWN = [
  "BELUM DIKALIBRASI",
  "SUDAH DIKALIBRASI"
];

const LIST_12_BULAN = [
  "SEMUA BULAN",
  "Januari",
  "Februari",
  "Maret",
  "April",
  "Mei",
  "Juni",
  "Juli",
  "Agustus",
  "September",
  "Oktober",
  "November",
  "Desember"
];

const LIST_TAHUN = [
  "SEMUA TAHUN",
  "2026",
  "2025",
  "2024",
  "2027"
];

/**
 * Helper Cepat: Bersihkan Slicer Lama
 */
function cleanOldSlicers_(sheet) {
  try {
    const slicers = sheet.getSlicers();
    if (slicers && slicers.length > 0) {
      slicers.forEach(function(s) {
        try { s.remove(); } catch(e) {}
      });
    }
  } catch (e) {}
}

/**
 * Helper Cepat: Pasang Warna Zebra Striping dalam 1 Batch (Sangat Cepat <0.1 Detik)
 */
function applyZebraFast_(range, numRows, numCols) {
  if (numRows <= 0 || numCols <= 0) return;
  const backgrounds = [];
  for (let r = 0; r < numRows; r++) {
    const color = (r % 2 === 0) ? "#ffffff" : "#f1f5f9";
    const row = [];
    for (let c = 0; c < numCols; c++) {
      row.push(color);
    }
    backgrounds.push(row);
  }
  range.setBackgrounds(backgrounds);
}

/**
 * 1. Merender tabel data ke Sheet 'Alkes' (Tanpa Filter, Tanpa Kolom Status Kalibrasi)
 */
function renderAlkesViewSheet(sheet, rawData) {
  cleanOldSlicers_(sheet);

  const headers = [
    'No',
    'Nama Barang',
    'Merk',
    'Tipe',
    'Seri Number',
    'Tahun',
    'Ruang Pemilik',
    'Lokasi Alkes',
    'Kondisi',
    'Keterangan'
  ];

  const rows = [headers];

  rawData.forEach(function(item, index) {
    let rawKondisi = item.kondisi || 'Baik';
    if (rawKondisi.indexOf('Baik') !== -1) rawKondisi = 'Baik';
    else if (rawKondisi.indexOf('Rusak Ringan') !== -1) rawKondisi = 'Rusak Ringan';
    else if (rawKondisi.indexOf('Rusak Berat') !== -1) rawKondisi = 'Rusak Berat';

    rows.push([
      index + 1,
      item.nama_barang || '',
      item.merk || '-',
      item.tipe || '-',
      item.seri_number || '-',
      item.tahun || '-',
      item.ruang_pemilik || 'CSSD',
      item.lokasi_alkes || item.lokasi_fisik || item.ruang_pemilik || 'CSSD',
      rawKondisi,
      item.keterangan || '-'
    ]);
  });

  sheet.clear();
  sheet.getRange(1, 1, Math.max(sheet.getMaxRows(), 100), Math.max(sheet.getMaxColumns(), 15)).clearDataValidations();

  // Pastikan seluruh baris tampil normal
  try {
    const maxR = sheet.getMaxRows();
    if (maxR > 1) sheet.showRows(1, maxR);
  } catch (e) {}

  // Hapus filter jika ada
  try {
    const existingFilter = sheet.getFilter();
    if (existingFilter) existingFilter.remove();
  } catch (e) {}

  const totalRows = rows.length;
  const totalCols = rows[0].length;

  const tableRange = sheet.getRange(1, 1, totalRows, totalCols);
  tableRange.setValues(rows);
  tableRange.setFontFamily("Inter");

  // Styling Header (Baris 1)
  const headerRange = sheet.getRange(1, 1, 1, totalCols);
  headerRange.setBackground("#064e3b")
             .setFontColor("#ffffff")
             .setFontWeight("bold")
             .setHorizontalAlignment("center")
             .setVerticalAlignment("middle")
             .setFontSize(11);
  sheet.setRowHeight(1, 38);

  // Styling Baris Data secara Batch Instan
  if (totalRows > 1) {
    const dataRowsCount = totalRows - 1;
    const dataRange = sheet.getRange(2, 1, dataRowsCount, totalCols);
    dataRange.setFontSize(10)
             .setFontColor("#0f172a")
             .setVerticalAlignment("middle");

    applyZebraFast_(dataRange, dataRowsCount, totalCols);
    sheet.setRowHeights(2, dataRowsCount, 28);

    sheet.getRange(2, 1, dataRowsCount, 1).setHorizontalAlignment("center"); // No (A)
    sheet.getRange(2, 6, dataRowsCount, 1).setHorizontalAlignment("center"); // Tahun (F)
    sheet.getRange(2, 9, dataRowsCount, 1).setHorizontalAlignment("center"); // Kondisi (I)
  }

  tableRange.setBorder(true, true, true, true, true, true, "#cbd5e1", SpreadsheetApp.BorderStyle.SOLID);

  const columnWidths = {
    1: 55,  // No (A)
    2: 220, // Nama Barang (B)
    3: 140, // Merk (C)
    4: 140, // Tipe (D)
    5: 150, // Seri Number (E)
    6: 65,  // Tahun (F)
    7: 140, // Ruang Pemilik (G)
    8: 140, // Lokasi Alkes (H)
    9: 120, // Kondisi (I)
    10: 180 // Keterangan (J)
  };

  for (let colIndex = 1; colIndex <= 10; colIndex++) {
    sheet.setColumnWidth(colIndex, columnWidths[colIndex] || 140);
  }

  sheet.setFrozenRows(1);
  sheet.setFrozenColumns(1);
}

/**
 * 2. Merender tabel data ke Sheet 'Perbaikan' (Dropdown Bulan & Tahun di Baris 1 Kolom P & Q)
 */
function renderPerbaikanSheet(sheet, rawData) {
  cleanOldSlicers_(sheet);

  const headers = [
    'No',
    'Nama Barang',
    'Merk / Tipe',
    'Seri Number',
    'Ruang Pemilik',
    'Jenis Tindakan',
    'Bulan Pelaporan',
    'Tanggal Lapor',
    'Tanggal Selesai',
    'Durasi Pengerjaan',
    'Pelaksana / Vendor',
    'Deskripsi Kerusakan',
    'Tindakan Perbaikan',
    'Status Hasil'
  ];

  const rows = [headers];

  rawData.forEach(function(item, index) {
    rows.push([
      index + 1,
      item.nama_barang || '-',
      item.merk_tipe || '-',
      item.seri_number || '-',
      item.ruang_pemilik || '-',
      item.jenis_tindakan || 'Perbaikan',
      item.bulan_pelaporan || '-',
      item.tanggal_mulai || '-',
      item.tanggal_selesai || 'Dalam Proses',
      item.durasi_pengerjaan || '1 Hari',
      item.pelaksana_vendor || 'Teknisi Elektromedis RS',
      item.deskripsi_kerusakan || '-',
      item.tindakan_perbaikan || '-',
      item.status_hasil || 'Proses'
    ]);
  });

  sheet.clear();
  sheet.getRange(1, 1, Math.max(sheet.getMaxRows(), 50), Math.max(sheet.getMaxColumns(), 18)).clearDataValidations();

  // Pastikan seluruh baris tampil normal
  try {
    const maxR = sheet.getMaxRows();
    if (maxR > 1) sheet.showRows(1, maxR);
  } catch (e) {}

  // Hapus filter header jika ada
  try {
    const existingFilter = sheet.getFilter();
    if (existingFilter) existingFilter.remove();
  } catch (e) {}

  const totalRows = rows.length;
  const totalCols = rows[0].length;

  const tableRange = sheet.getRange(1, 1, totalRows, totalCols);
  tableRange.setValues(rows);
  tableRange.setFontFamily("Inter");

  // Styling Header (Baris 1)
  const headerRange = sheet.getRange(1, 1, 1, totalCols);
  headerRange.setBackground("#064e3b")
             .setFontColor("#ffffff")
             .setFontWeight("bold")
             .setHorizontalAlignment("center")
             .setVerticalAlignment("middle")
             .setFontSize(11);
  sheet.setRowHeight(1, 38);

  // Styling Baris Data secara Batch Instan
  if (totalRows > 1) {
    const dataRowsCount = totalRows - 1;
    const dataRange = sheet.getRange(2, 1, dataRowsCount, totalCols);
    dataRange.setFontSize(10)
             .setFontColor("#0f172a")
             .setVerticalAlignment("middle");

    applyZebraFast_(dataRange, dataRowsCount, totalCols);
    sheet.setRowHeights(2, dataRowsCount, 28);

    sheet.getRange(2, 1, dataRowsCount, 1).setHorizontalAlignment("center"); // No (A)
    sheet.getRange(2, 6, dataRowsCount, 5).setHorizontalAlignment("center"); // Jenis, Bulan, Tgl Mulai, Tgl Selesai, Durasi (F, G, H, I, J)
    sheet.getRange(2, 14, dataRowsCount, 1).setHorizontalAlignment("center"); // Status Hasil (N)
  }

  tableRange.setBorder(true, true, true, true, true, true, "#cbd5e1", SpreadsheetApp.BorderStyle.SOLID);

  const columnWidths = {
    1: 50,  // No (A)
    2: 220, // Nama Barang (B)
    3: 160, // Merk / Tipe (C)
    4: 140, // Seri Number (D)
    5: 130, // Ruang Pemilik (E)
    6: 140, // Jenis Tindakan (F)
    7: 130, // Bulan Pelaporan (G)
    8: 130, // Tanggal Lapor (H)
    9: 130, // Tanggal Selesai (I)
    10: 120,// Durasi (J)
    11: 160,// Pelaksana (K)
    12: 220,// Deskripsi Kerusakan (L)
    13: 220,// Tindakan Perbaikan (M)
    14: 110 // Status Hasil (N)
  };

  for (let colIndex = 1; colIndex <= 14; colIndex++) {
    sheet.setColumnWidth(colIndex, columnWidths[colIndex] || 140);
  }

  // Kolom O sebagai pemisah
  sheet.setColumnWidth(15, 25);

  // ----------------------------------------------------
  // Pasang Dropdown Filter BULAN (P1) & TAHUN (Q1) di Baris 1 (FROZEN HEADER - SELALU TAMPAK)
  // ----------------------------------------------------
  sheet.setColumnWidth(16, 150); // Kolom P (Bulan)
  sheet.setColumnWidth(17, 130); // Kolom Q (Tahun)

  const bulanValidation = SpreadsheetApp.newDataValidation()
    .requireValueInList(LIST_12_BULAN, true)
    .setAllowInvalid(false)
    .build();

  const tahunValidation = SpreadsheetApp.newDataValidation()
    .requireValueInList(LIST_TAHUN, true)
    .setAllowInvalid(false)
    .build();

  // Dropdown Bulan di P1
  sheet.getRange(1, 16).setDataValidation(bulanValidation)
       .setValue("SEMUA BULAN")
       .setBackground("#ecfdf5")
       .setFontColor("#064e3b")
       .setFontWeight("bold")
       .setFontSize(10)
       .setHorizontalAlignment("center")
       .setVerticalAlignment("middle")
       .setBorder(true, true, true, true, true, true, "#064e3b", SpreadsheetApp.BorderStyle.SOLID_MEDIUM);

  // Dropdown Tahun di Q1
  sheet.getRange(1, 17).setDataValidation(tahunValidation)
       .setValue("SEMUA TAHUN")
       .setBackground("#ecfdf5")
       .setFontColor("#064e3b")
       .setFontWeight("bold")
       .setFontSize(10)
       .setHorizontalAlignment("center")
       .setVerticalAlignment("middle")
       .setBorder(true, true, true, true, true, true, "#064e3b", SpreadsheetApp.BorderStyle.SOLID_MEDIUM);

  sheet.setFrozenRows(1);
  sheet.setFrozenColumns(2);
}

/**
 * 3. Merender tabel data ke Sheet 'Kalibrasi' (Dropdown Bulan & Tahun di Baris 1 Kolom O & P)
 */
function renderKalibrasiSheet(sheet, rawData) {
  cleanOldSlicers_(sheet);

  const headers = [
    'No',
    'Nama Barang',
    'Merk',
    'Tipe',
    'Seri Number',
    'Ruang Pemilik',
    'Lokasi Alkes',
    'Status Kalibrasi',
    'Bulan Kalibrasi',
    'Kalibrasi Terakhir',
    'Kalibrasi Berikutnya',
    'Status Dokumen',
    'Keterangan'
  ];

  const rows = [headers];

  rawData.forEach(function(item, index) {
    rows.push([
      index + 1,
      item.nama_barang || '',
      item.merk || '-',
      item.tipe || '-',
      item.seri_number || '-',
      item.ruang_pemilik || '-',
      item.lokasi_alkes || item.lokasi_fisik || '-',
      item.status_kalibrasi || 'BELUM DIKALIBRASI',
      item.bulan_kalibrasi || '-',
      item.tanggal_kalibrasi_terakhir || 'Belum ada data',
      item.tanggal_kalibrasi_berikutnya || 'Belum dijadwalkan',
      item.status_sertifikat || 'Belum Ada',
      item.keterangan || '-'
    ]);
  });

  sheet.clear();
  sheet.getRange(1, 1, Math.max(sheet.getMaxRows(), 100), Math.max(sheet.getMaxColumns(), 17)).clearDataValidations();

  // Pastikan seluruh baris tampil normal
  try {
    const maxR = sheet.getMaxRows();
    if (maxR > 1) sheet.showRows(1, maxR);
  } catch (e) {}

  // Hapus filter header jika ada
  try {
    const existingFilter = sheet.getFilter();
    if (existingFilter) existingFilter.remove();
  } catch (e) {}

  const totalRows = rows.length;
  const totalCols = rows[0].length;

  const tableRange = sheet.getRange(1, 1, totalRows, totalCols);
  tableRange.setValues(rows);
  tableRange.setFontFamily("Inter");

  // Styling Header (Baris 1)
  const headerRange = sheet.getRange(1, 1, 1, totalCols);
  headerRange.setBackground("#064e3b")
             .setFontColor("#ffffff")
             .setFontWeight("bold")
             .setHorizontalAlignment("center")
             .setVerticalAlignment("middle")
             .setFontSize(11);
  sheet.setRowHeight(1, 38);

  // Styling Baris Data secara Batch Instan
  if (totalRows > 1) {
    const dataRowsCount = totalRows - 1;
    const dataRange = sheet.getRange(2, 1, dataRowsCount, totalCols);
    dataRange.setFontSize(10)
             .setFontColor("#0f172a")
             .setVerticalAlignment("middle");

    applyZebraFast_(dataRange, dataRowsCount, totalCols);
    sheet.setRowHeights(2, dataRowsCount, 28);

    sheet.getRange(2, 1, dataRowsCount, 1).setHorizontalAlignment("center"); // No (A)
    sheet.getRange(2, 8, dataRowsCount, 5).setHorizontalAlignment("center"); // Status, Bulan, Tgl Terakhir, Tgl Berikutnya, Status Dokumen (H, I, J, K, L)
  }

  tableRange.setBorder(true, true, true, true, true, true, "#cbd5e1", SpreadsheetApp.BorderStyle.SOLID);

  const columnWidths = {
    1: 50,  // No (A)
    2: 220, // Nama Barang (B)
    3: 140, // Merk (C)
    4: 140, // Tipe (D)
    5: 150, // Seri Number (E)
    6: 140, // Ruang Pemilik (F)
    7: 140, // Lokasi Alkes (G)
    8: 150, // Status Kalibrasi (H)
    9: 130, // Bulan Kalibrasi (I)
    10: 130,// Kalibrasi Terakhir (J)
    11: 130,// Kalibrasi Berikutnya (K)
    12: 120,// Status Dokumen (L)
    13: 180 // Keterangan (M)
  };

  for (let colIndex = 1; colIndex <= 13; colIndex++) {
    sheet.setColumnWidth(colIndex, columnWidths[colIndex] || 140);
  }

  // Kolom N sebagai pemisah
  sheet.setColumnWidth(14, 25);

  // ----------------------------------------------------
  // Pasang Dropdown Filter BULAN (O1) & TAHUN (P1) di Baris 1 (FROZEN HEADER - SELALU TAMPAK)
  // ----------------------------------------------------
  sheet.setColumnWidth(15, 150); // Kolom O (Bulan)
  sheet.setColumnWidth(16, 130); // Kolom P (Tahun)

  const bulanValidation = SpreadsheetApp.newDataValidation()
    .requireValueInList(LIST_12_BULAN, true)
    .setAllowInvalid(false)
    .build();

  const tahunValidation = SpreadsheetApp.newDataValidation()
    .requireValueInList(LIST_TAHUN, true)
    .setAllowInvalid(false)
    .build();

  // Dropdown Bulan di O1
  sheet.getRange(1, 15).setDataValidation(bulanValidation)
       .setValue("SEMUA BULAN")
       .setBackground("#ecfdf5")
       .setFontColor("#064e3b")
       .setFontWeight("bold")
       .setFontSize(10)
       .setHorizontalAlignment("center")
       .setVerticalAlignment("middle")
       .setBorder(true, true, true, true, true, true, "#064e3b", SpreadsheetApp.BorderStyle.SOLID_MEDIUM);

  // Dropdown Tahun di P1
  sheet.getRange(1, 16).setDataValidation(tahunValidation)
       .setValue("SEMUA TAHUN")
       .setBackground("#ecfdf5")
       .setFontColor("#064e3b")
       .setFontWeight("bold")
       .setFontSize(10)
       .setHorizontalAlignment("center")
       .setVerticalAlignment("middle")
       .setBorder(true, true, true, true, true, true, "#064e3b", SpreadsheetApp.BorderStyle.SOLID_MEDIUM);

  sheet.setFrozenRows(1);
  sheet.setFrozenColumns(2);
}

/**
 * 4. Menyiapkan Sheet 'Tambah Alkes' (Mulai dari A1)
 */
function setupInputAlkesSheet(sheet) {
  cleanOldSlicers_(sheet);

  sheet.clear();
  sheet.getRange(1, 1, Math.max(sheet.getMaxRows(), 100), Math.max(sheet.getMaxColumns(), 15)).clearDataValidations();

  // Header Kolom Input langsung di Baris 1 (10 Kolom)
  const inputHeaders = [
    'Nama Barang',
    'Merk',
    'Tipe',
    'Seri Number',
    'Tahun',
    'Ruang Pemilik',
    'Lokasi Alkes',
    'Kondisi',
    'Status Kalibrasi',
    'Keterangan'
  ];

  const headerRange = sheet.getRange(1, 1, 1, 10);
  headerRange.setValues([inputHeaders])
             .setBackground("#064e3b") // Emerald Dark
             .setFontColor("#ffffff")
             .setFontWeight("bold")
             .setHorizontalAlignment("center")
             .setVerticalAlignment("middle")
             .setFontSize(11)
             .setFontFamily("Inter");
  sheet.setRowHeight(1, 38);

  // Siapkan 20 baris kosong siap isi (Baris 2 s/d 21) dengan desain Zebra Striping
  const maxInputRows = 20;
  const inputArea = sheet.getRange(2, 1, maxInputRows, 10);
  inputArea.setFontFamily("Inter")
           .setFontSize(10)
           .setFontColor("#0f172a")
           .setVerticalAlignment("middle");

  applyZebraFast_(inputArea, maxInputRows, 10);
  sheet.setRowHeights(2, maxInputRows, 28);

  // Perataan Kolom
  sheet.getRange(2, 5, maxInputRows, 1).setHorizontalAlignment("center"); // Tahun (E)
  sheet.getRange(2, 8, maxInputRows, 2).setHorizontalAlignment("center"); // Kondisi, Status Kalibrasi (H, I)

  // Border Area Input
  sheet.getRange(1, 1, maxInputRows + 1, 10).setBorder(
    true, true, true, true, true, true,
    "#cbd5e1", SpreadsheetApp.BorderStyle.SOLID
  );

  // Dropdown Validation untuk Ruangan
  const roomValidation = SpreadsheetApp.newDataValidation()
    .requireValueInList(RUANGAN_LIST_DROPDOWN, true)
    .setAllowInvalid(true)
    .build();

  // Dropdown Validation untuk Kondisi
  const kondisiValidation = SpreadsheetApp.newDataValidation()
    .requireValueInList(KONDISI_LIST_DROPDOWN, true)
    .setAllowInvalid(true)
    .build();

  // Dropdown Validation untuk Status Kalibrasi
  const kalibrasiValidation = SpreadsheetApp.newDataValidation()
    .requireValueInList(KALIBRASI_LIST_DROPDOWN, true)
    .setAllowInvalid(true)
    .build();

  // Pasang Dropdown Ruang Pemilik (Kolom F / index 6)
  sheet.getRange(2, 6, maxInputRows, 1).setDataValidation(roomValidation);

  // Pasang Dropdown Lokasi Alkes (Kolom G / index 7)
  sheet.getRange(2, 7, maxInputRows, 1).setDataValidation(roomValidation);

  // Pasang Dropdown Kondisi (Kolom H / index 8)
  sheet.getRange(2, 8, maxInputRows, 1).setDataValidation(kondisiValidation);

  // Pasang Dropdown Status Kalibrasi (Kolom I / index 9)
  sheet.getRange(2, 9, maxInputRows, 1).setDataValidation(kalibrasiValidation);

  // Lebar Kolom Form Input
  const inputWidths = {
    1: 220, // Nama Barang (A)
    2: 140, // Merk (B)
    3: 140, // Tipe (C)
    4: 150, // Seri Number (D)
    5: 65,  // Tahun (E)
    6: 140, // Ruang Pemilik (F)
    7: 140, // Lokasi Alkes (G)
    8: 120, // Kondisi (H)
    9: 150, // Status Kalibrasi (I)
    10: 180 // Keterangan (J)
  };

  for (let colIndex = 1; colIndex <= 10; colIndex++) {
    sheet.setColumnWidth(colIndex, inputWidths[colIndex] || 140);
  }

  sheet.setFrozenRows(1);
}

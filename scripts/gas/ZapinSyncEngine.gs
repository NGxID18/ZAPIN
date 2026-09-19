/**
 * ==============================================================================
 * 🏥 ZAPIN SYNC ENGINE (Google Apps Script)
 * Sistem Sinkronisasi Dua Arah Instan (Bi-Directional Real-Time Sync)
 * ZAPIN (RSJKO Sambang Lihum) <--> Google Spreadsheet
 * ==============================================================================
 * 
 * CARA PEMASANGAN (HANYA BUTUH WAKTU 1 MENIT):
 * 1. Di Google Spreadsheet Anda, klik menu: Ekstensi (Extensions) > Apps Script.
 * 2. Hapus seluruh isi default pada Code.gs, lalu paste seluruh kode di bawah ini.
 * 3. Simpan proyek dengan menekan ikon Disket (Ctrl + S / Cmd + S).
 * 4. Untuk mengaktifkan Website -> Spreadsheet:
 *    - Klik menu biru "Terapkan" (Deploy) di kanan atas > "Penerapan baru" (New deployment).
 *    - Pilih jenis: "Aplikasi Web" (Web app).
 *    - Konfigurasi:
 *      * Deskripsi: Zapin Webhook Engine
 *      * Jalankan sebagai (Execute as): Saya (email Anda)
 *      * Siapa yang memiliki akses (Who has access): Siapa saja (Anyone)
 *    - Klik "Terapkan" (Deploy) dan berikan izin otorisasi jika diminta.
 *    - Salin URL Aplikasi Web yang diberikan (format: https://script.google.com/macros/s/.../exec).
 *    - Tempelkan URL tersebut ke file .env di server ZAPIN pada GOOGLE_SHEET_WEBHOOK_URL.
 * 
 * 5. Untuk mengaktifkan Spreadsheet -> Website:
 *    - Muat ulang (Refresh) halaman Google Spreadsheet Anda.
 *    - Akan muncul menu baru di atas bernama "⚡ ZAPIN Sync".
 *    - Klik "⚡ ZAPIN Sync" > "1. Pasang Otomatis Trigger Sinkronisasi (onEdit)".
 *    - Selesai! Sekarang saat ada sel yang diedit di spreadsheet, data langsung terupdate di web ZAPIN!
 * ==============================================================================
 */

// ==========================================
// 1. KONFIGURASI INTEGRASI ZAPIN
// ==========================================
var ZAPIN_CONFIG = {
  // Alamat endpoint webhook ZAPIN
  API_URL: 'https://siakers.biz.id/api/sheets/webhook-update',
  PING_URL: 'https://siakers.biz.id/api/ping',
  
  // Kunci API rahasia yang sama dengan ZAPIN_API_KEY di file .env ZAPIN
  SECRET_KEY: 'zapin_secret_key_rsjko_2026',
  
  // Struktur Baris Dokumen
  HEADER_ROW: 9,       // Baris 9 adalah header kolom (No., Nama Barang, Merk, dst)
  DATA_START_ROW: 10,  // Baris 10 adalah data baris pertama (No. 1)
  
  // Nama sheet utama (null jika otomatis menggunakan sheet yang aktif)
  SHEET_NAME: null
};

// Pemetaan Indeks Kolom Google Spreadsheet (1-Indexed: A=1, B=2, C=3, ...)
var COLUMN_MAP = {
  no_urut: 2,          // Kolom B: No. (1..638)
  nama_barang: 3,      // Kolom C: Nama Barang
  merk: 4,             // Kolom D: Merk
  tipe: 5,             // Kolom E: Tipe
  nomor_seri: 6,       // Kolom F: Serial Number
  tahun: 7,            // Kolom G: Tahun
  jumlah: 8,           // Kolom H: Jumlah
  cara_perolehan: 9,   // Kolom I: Cara Perolehan
  nilai_perolehan: 10, // Kolom J: Nilai Perolehan
  distributor: 11,     // Kolom K: Distributor
  ruangan: 12,         // Kolom L: Ruangan
  lokasi_saat_ini: 13, // Kolom M: Lokasi Saat Ini
  kondisi: 14,         // Kolom N: Kondisi Alat
  aspak: 15,           // Kolom O: ASPAK
  kib: 16,             // Kolom P: KIB
  non_kib_dan_aspak: 17,// Kolom Q: NON KIB dan ASPAK
  akl_akd: 18,         // Kolom R: AKL/AKD
  keterangan: 19       // Kolom S: KETERANGAN
};

// ==========================================
// 2. WEBHOOK MASUK (WEBSITE -> SPREADSHEET)
// ==========================================
/**
 * Menangani HTTP POST dari website ZAPIN ketika ada data alkes yang diperbarui.
 */
function doPost(e) {
  var lock = LockService.getScriptLock();
  try {
    lock.waitLock(10000); // Tunggu hingga 10 detik jika ada operasi bersamaan
    
    var content = e.postData.contents;
    var json = JSON.parse(content);
    
    // Verifikasi Secret Key
    if (!json.secret || json.secret !== ZAPIN_CONFIG.SECRET_KEY) {
      return ContentService.createTextOutput(JSON.stringify({
        status: 'error',
        message: 'Unauthorized: Kunci rahasia (secret key) tidak valid.'
      })).setMimeType(ContentService.MimeType.JSON);
    }
    
    var alkesData = json.data || json;
    var targetNoUrut = parseInt(alkesData.no_urut, 10);
    
    if (isNaN(targetNoUrut)) {
      return ContentService.createTextOutput(JSON.stringify({
        status: 'error',
        message: 'Parameter no_urut wajib disertakan dan harus berupa angka.'
      })).setMimeType(ContentService.MimeType.JSON);
    }
    
    var sheet = getTargetSheet();
    var lastRow = sheet.getLastRow();
    
    if (lastRow < ZAPIN_CONFIG.DATA_START_ROW) {
      return ContentService.createTextOutput(JSON.stringify({
        status: 'error',
        message: 'Spreadsheet belum memiliki baris data.'
      })).setMimeType(ContentService.MimeType.JSON);
    }
    
    // Baca seluruh Kolom B (No. Urut) untuk pencarian instan
    var numRows = lastRow - ZAPIN_CONFIG.DATA_START_ROW + 1;
    var noValues = sheet.getRange(ZAPIN_CONFIG.DATA_START_ROW, COLUMN_MAP.no_urut, numRows, 1).getValues();
    
    var matchedRow = -1;
    for (var i = 0; i < noValues.length; i++) {
      if (parseInt(noValues[i][0], 10) === targetNoUrut) {
        matchedRow = ZAPIN_CONFIG.DATA_START_ROW + i;
        break;
      }
    }
    
    // Jika tidak ditemukan baris lama, tambahkan sebagai baris baru di paling bawah
    if (matchedRow === -1) {
      matchedRow = lastRow + 1;
      sheet.getRange(matchedRow, COLUMN_MAP.no_urut).setValue(targetNoUrut);
    }
    
    // Flag penanda agar onEdit tidak memantul balik (loop prevention)
    PropertiesService.getScriptProperties().setProperty('IS_SYNCING', 'true');
    
    // Update setiap kolom yang tersedia dalam payload
    for (var key in COLUMN_MAP) {
      if (key === 'no_urut') continue; // no_urut tidak diubah
      
      if (alkesData.hasOwnProperty(key) && alkesData[key] !== undefined) {
        var colIndex = COLUMN_MAP[key];
        var val = alkesData[key];
        sheet.getRange(matchedRow, colIndex).setValue(val === null ? '' : val);
      }
    }
    
    SpreadsheetApp.flush();
    PropertiesService.getScriptProperties().deleteProperty('IS_SYNCING');
    
    return ContentService.createTextOutput(JSON.stringify({
      status: 'success',
      message: 'Baris ' + matchedRow + ' (No. ' + targetNoUrut + ') berhasil diperbarui di Google Spreadsheet.',
      row_index: matchedRow,
      no_urut: targetNoUrut
    })).setMimeType(ContentService.MimeType.JSON);
    
  } catch (err) {
    PropertiesService.getScriptProperties().deleteProperty('IS_SYNCING');
    return ContentService.createTextOutput(JSON.stringify({
      status: 'error',
      message: 'Terjadi kegagalan pemrosesan: ' + err.toString()
    })).setMimeType(ContentService.MimeType.JSON);
  } finally {
    lock.releaseLock();
  }
}

/**
 * Mendukung HTTP GET untuk health check Web App.
 */
function doGet(e) {
  return ContentService.createTextOutput(JSON.stringify({
    status: 'success',
    message: 'ZAPIN Sync Engine Web App aktif & siap menerima permintaan!',
    timestamp: new Date().toISOString()
  })).setMimeType(ContentService.MimeType.JSON);
}

// ==========================================
// 3. EVENT ON-EDIT (SPREADSHEET -> WEBSITE)
// ==========================================
/**
 * Fungsi trigger otomatis yang dipanggil saat ada sel di Google Sheet yang diedit oleh user.
 */
function onSheetEdit(e) {
  try {
    // Loop prevention check
    var isSyncing = PropertiesService.getScriptProperties().getProperty('IS_SYNCING');
    if (isSyncing === 'true') {
      return;
    }
    
    var range = e.range;
    var editedRow = range.getRow();
    var sheet = range.getSheet();
    
    // Abaikan jika yang diedit adalah baris header atau di atas baris data
    if (editedRow < ZAPIN_CONFIG.DATA_START_ROW) {
      return;
    }
    
    // Ambil nomor urut pada baris tersebut (Kolom B)
    var noUrutVal = sheet.getRange(editedRow, COLUMN_MAP.no_urut).getValue();
    var noUrut = parseInt(noUrutVal, 10);
    if (isNaN(noUrut) || noUrut <= 0) {
      return; // Baris belum memiliki No. Urut yang valid
    }
    
    // Ambil seluruh data baris (Kolom A sampai S = 19 kolom)
    var rowValues = sheet.getRange(editedRow, 1, 1, 19).getValues()[0];
    
    var payloadData = {
      no_urut: noUrut,
      nama_barang: rowValues[COLUMN_MAP.nama_barang - 1],
      merk: rowValues[COLUMN_MAP.merk - 1],
      tipe: rowValues[COLUMN_MAP.tipe - 1],
      nomor_seri: rowValues[COLUMN_MAP.nomor_seri - 1],
      tahun: rowValues[COLUMN_MAP.tahun - 1],
      jumlah: rowValues[COLUMN_MAP.jumlah - 1],
      cara_perolehan: rowValues[COLUMN_MAP.cara_perolehan - 1],
      nilai_perolehan: rowValues[COLUMN_MAP.nilai_perolehan - 1],
      distributor: rowValues[COLUMN_MAP.distributor - 1],
      ruangan: rowValues[COLUMN_MAP.ruangan - 1],
      lokasi_saat_ini: rowValues[COLUMN_MAP.lokasi_saat_ini - 1],
      kondisi: rowValues[COLUMN_MAP.kondisi - 1],
      aspak: rowValues[COLUMN_MAP.aspak - 1],
      kib: rowValues[COLUMN_MAP.kib - 1],
      non_kib_dan_aspak: rowValues[COLUMN_MAP.non_kib_dan_aspak - 1],
      akl_akd: rowValues[COLUMN_MAP.akl_akd - 1],
      keterangan: rowValues[COLUMN_MAP.keterangan - 1]
    };
    
    var requestOptions = {
      method: 'post',
      contentType: 'application/json',
      headers: {
        'X-Zapin-Secret': ZAPIN_CONFIG.SECRET_KEY
      },
      payload: JSON.stringify({
        secret: ZAPIN_CONFIG.SECRET_KEY,
        action: 'sheet_row_edited',
        row_index: editedRow,
        data: payloadData
      }),
      muteHttpExceptions: true
    };
    
    UrlFetchApp.fetch(ZAPIN_CONFIG.API_URL, requestOptions);
    
  } catch (err) {
    Logger.log('Error saat sinkronisasi onSheetEdit: ' + err.toString());
  }
}

// ==========================================
// 4. MENU UI & KEMUDAHAN ADMINISTRATOR
// ==========================================
/**
 * Menambahkan menu kustom "⚡ ZAPIN Sync" di bilah menu Google Sheets.
 */
function onOpen() {
  var ui = SpreadsheetApp.getUi();
  ui.createMenu('⚡ ZAPIN Sync')
    .addItem('1. Pasang Otomatis Trigger Sinkronisasi (onEdit)', 'setupTriggers')
    .addItem('2. Tes Koneksi ke Website ZAPIN', 'testConnection')
    .addSeparator()
    .addItem('3. Sinkronkan Baris yang Sedang Dipilih', 'syncSelectedRow')
    .addToUi();
}

/**
 * Memasang trigger onEdit secara otomatis dengan satu klik.
 */
function setupTriggers() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var triggers = ScriptApp.getProjectTriggers();
  
  // Hapus trigger lama jika ada agar tidak dobel
  for (var i = 0; i < triggers.length; i++) {
    if (triggers[i].getHandlerFunction() === 'onSheetEdit') {
      ScriptApp.deleteTrigger(triggers[i]);
    }
  }
  
  // Buat trigger baru untuk event onEdit spreadsheet
  ScriptApp.newTrigger('onSheetEdit')
    .forSpreadsheet(ss)
    .onEdit()
    .create();
    
  SpreadsheetApp.getUi().alert(
    '✅ Berhasil!',
    'Trigger sinkronisasi otomatis ZAPIN berhasil dipasang!\n\nSetiap perubahan data alkes di spreadsheet ini akan langsung terupdate ke website ZAPIN secara instan.',
    SpreadsheetApp.getUi().ButtonSet.OK
  );
}

/**
 * Mengetes koneksi ke endpoint API ZAPIN.
 */
function testConnection() {
  var ui = SpreadsheetApp.getUi();
  try {
    var response = UrlFetchApp.fetch(ZAPIN_CONFIG.PING_URL, {
      muteHttpExceptions: true
    });
    
    if (response.getResponseCode() === 200) {
      ui.alert(
        '✅ Koneksi Sukses!',
        'Server ZAPIN terhubung dengan baik!\nStatus: 200 OK\nRespons: ' + response.getContentText(),
        ui.ButtonSet.OK
      );
    } else {
      ui.alert(
        '⚠️ Gagal Terhubung',
        'Server ZAPIN merespons dengan HTTP ' + response.getResponseCode() + ':\n' + response.getContentText(),
        ui.ButtonSet.OK
      );
    }
  } catch (err) {
    ui.alert(
      '❌ Terjadi Kesalahan',
      'Tidak dapat menghubungi server ZAPIN:\n' + err.toString(),
      ui.ButtonSet.OK
    );
  }
}

/**
 * Menyinkronkan baris yang sedang aktif dipilih oleh kursor.
 */
function syncSelectedRow() {
  var ui = SpreadsheetApp.getUi();
  var sheet = getTargetSheet();
  var activeCell = sheet.getActiveCell();
  var row = activeCell.getRow();
  
  if (row < ZAPIN_CONFIG.DATA_START_ROW) {
    ui.alert('Peringatan', 'Silakan pilih sel pada baris data alkes (baris ' + ZAPIN_CONFIG.DATA_START_ROW + ' ke bawah).', ui.ButtonSet.OK);
    return;
  }
  
  onSheetEdit({
    range: sheet.getRange(row, 1)
  });
  
  ui.alert('Sukses', 'Baris ' + row + ' berhasil dikirim ke website ZAPIN!', ui.ButtonSet.OK);
}

/**
 * Helper untuk mendapatkan sheet target yang aktif.
 */
function getTargetSheet() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  if (ZAPIN_CONFIG.SHEET_NAME) {
    var s = ss.getSheetByName(ZAPIN_CONFIG.SHEET_NAME);
    if (s) return s;
  }
  return ss.getActiveSheet();
}


/**
 * Konfigurasi dan Pemetaan Kolom ZAPIN Sync.
 */

function getSecretKey() {
  var prop = PropertiesService.getScriptProperties().getProperty('ZAPIN_SECRET_KEY');
  return prop || 'zapin_secret_key_rsjko_2026';
}

var CONFIG = {
  API_URL: 'https://zapin.online/api/sheets/webhook-update',
  PING_URL: 'https://zapin.online/api/ping',
  SECRET_KEY: getSecretKey(),
  HEADER_ROW: 8,
  DATA_START_ROW: 9,
  SHEET_NAME: 'Sheet1'
};

var COLUMN_MAP = {
  no_urut: 2,
  nama_barang: 3,
  merk: 4,
  tipe: 5,
  nomor_seri: 6,
  tahun: 7,
  jumlah: 8,
  cara_perolehan: 9,
  nilai_perolehan: 10,
  distributor: 11,
  ruangan: 12,
  lokasi_saat_ini: 13,
  kondisi: 14,
  aspak: 15,
  kib: 16,
  non_kib_dan_aspak: 17,
  akl_akd: 18,
  keterangan: 19
};

function getTargetSheet() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  if (CONFIG.SHEET_NAME) {
    var s = ss.getSheetByName(CONFIG.SHEET_NAME);
    if (s) return s;
  }
  return ss.getActiveSheet();
}

function jsonResponse(data) {
  return ContentService.createTextOutput(JSON.stringify(data))
    .setMimeType(ContentService.MimeType.JSON);
}


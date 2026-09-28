/**
 * Menu UI dan Aksi Manual di Antarmuka Google Spreadsheet.
 */

function onOpen() {
  SpreadsheetApp.getUi().createMenu('ZAPIN')
    .addItem('Perbaiki Nomor Urut Otomatis (1 s/d Akhir)', 'perbaikiNomorUrutOtomatis')
    .addItem('Kirim Baris yang Dipilih', 'syncSelectedRow')
    .addItem('Sinkronkan Penghapusan ke ZAPIN', 'manualReconcile')
    .addItem('Tes Koneksi ke ZAPIN', 'testConnection')
    .addSeparator()
    .addItem('Atur Kunci Rahasia (Secret Key)', 'setSecretKeyViaPrompt')
    .addToUi();
}

function perbaikiNomorUrutOtomatis() {
  var ui = SpreadsheetApp.getUi();
  var sheet = getTargetSheet();
  var lastRow = sheet.getLastRow();
  if (lastRow < CONFIG.DATA_START_ROW) {
    ui.alert('Peringatan', 'Tidak ada data alkes untuk diperbaiki.', ui.ButtonSet.OK);
    return;
  }
  
  var numRows = lastRow - CONFIG.DATA_START_ROW + 1;
  var confirm = ui.alert('Konfirmasi', 'Apakah Anda ingin menata ulang seluruh nomor urut (kolom No.) dari 1 sampai ' + numRows + ' secara berurutan?', ui.ButtonSet.YES_NO);
  if (confirm !== ui.Button.YES) return;

  var numbers = [];
  for (var i = 1; i <= numRows; i++) {
    numbers.push([i]);
  }
  
  PropertiesService.getScriptProperties().setProperty('IS_SYNCING', 'true');
  if (CONFIG.DATA_START_ROW > 2) {
    sheet.getRange(2, COLUMN_MAP.no_urut, CONFIG.DATA_START_ROW - 2, 1).clearContent();
  }
  sheet.getRange(CONFIG.DATA_START_ROW, COLUMN_MAP.no_urut, numRows, 1).setValues(numbers);
  SpreadsheetApp.flush();
  PropertiesService.getScriptProperties().deleteProperty('IS_SYNCING');
  
  ui.alert('Sukses', 'Nomor urut kolom B berhasil diperbaiki secara otomatis menjadi 1 s/d ' + numRows + '.', ui.ButtonSet.OK);
}

function setSecretKeyViaPrompt() {
  var ui = SpreadsheetApp.getUi();
  var result = ui.prompt('Konfigurasi Keamanan ZAPIN', 'Masukkan Kunci Rahasia API ZAPIN (ZAPIN_SECRET_KEY):', ui.ButtonSet.OK_CANCEL);
  if (result.getSelectedButton() == ui.Button.OK) {
    var key = result.getResponseText().trim();
    if (key) {
      PropertiesService.getScriptProperties().setProperty('ZAPIN_SECRET_KEY', key);
      ui.alert('Sukses', 'Kunci rahasia ZAPIN berhasil disimpan dengan aman di Script Properties.', ui.ButtonSet.OK);
    }
  }
}

function manualReconcile() {
  var ui = SpreadsheetApp.getUi();
  try {
    syncActiveRowsToZapin();
    ui.alert('Sinkronisasi Sukses', 'Daftar data aktif berhasil disinkronkan ke ZAPIN. Baris yang telah dihapus di spreadsheet telah diselaraskan.', ui.ButtonSet.OK);
  } catch (err) {
    ui.alert('Gagal Sinkronisasi', 'Terjadi kesalahan: ' + err.toString(), ui.ButtonSet.OK);
  }
}

function setupTriggers() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var triggers = ScriptApp.getProjectTriggers();
  
  for (var i = 0; i < triggers.length; i++) {
    var handler = triggers[i].getHandlerFunction();
    if (handler === 'onSheetEdit' || handler === 'onSheetChange') {
      ScriptApp.deleteTrigger(triggers[i]);
    }
  }
  
  ScriptApp.newTrigger('onSheetEdit')
    .forSpreadsheet(ss)
    .onEdit()
    .create();

  ScriptApp.newTrigger('onSheetChange')
    .forSpreadsheet(ss)
    .onChange()
    .create();
    
  Logger.log('Trigger sinkronisasi otomatis ZAPIN (Pembaruan & Penghapusan) berhasil dipasang.');
}

function testConnection() {
  var ui = SpreadsheetApp.getUi();
  try {
    var response = UrlFetchApp.fetch(CONFIG.PING_URL, { muteHttpExceptions: true });
    if (response.getResponseCode() === 200) {
      ui.alert('Koneksi Sukses', 'Server ZAPIN terhubung normal (200 OK).', ui.ButtonSet.OK);
    } else {
      ui.alert('Koneksi Gagal', 'Respons server: HTTP ' + response.getResponseCode(), ui.ButtonSet.OK);
    }
  } catch (err) {
    ui.alert('Kesalahan', 'Gagal terhubung: ' + err.toString(), ui.ButtonSet.OK);
  }
}

function syncSelectedRow() {
  var sheet = getTargetSheet();
  var row = sheet.getActiveCell().getRow();
  if (row < CONFIG.DATA_START_ROW) {
    SpreadsheetApp.getUi().alert('Pilih baris data alkes (mulai baris ' + CONFIG.DATA_START_ROW + ').');
    return;
  }
  onSheetEdit({ range: sheet.getRange(row, 1) });
  SpreadsheetApp.getUi().alert('Baris ' + row + ' telah dikirim ke ZAPIN.');
}


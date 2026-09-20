/**
 * Menu UI dan Aksi Manual di Antarmuka Google Spreadsheet.
 */

function onOpen() {
  SpreadsheetApp.getUi().createMenu('ZAPIN')
    .addItem('Kirim Baris yang Dipilih', 'syncSelectedRow')
    .addItem('Sinkronkan Penghapusan ke ZAPIN', 'manualReconcile')
    .addItem('Tes Koneksi ke ZAPIN', 'testConnection')
    .addToUi();
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


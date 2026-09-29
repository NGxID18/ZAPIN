/**
 * Menu UI dan Aksi Manual di Antarmuka Google Spreadsheet ZAPIN.
 */

function onOpen() {
  SpreadsheetApp.getUi().createMenu('ZAPIN')
    .addItem('Refresh Data dari ZAPIN', 'refreshDataFromZapin')
    .addItem('Tes Koneksi ke ZAPIN', 'testConnection')
    .addToUi();
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

function refreshDataFromZapin() {
  var ui = SpreadsheetApp.getUi();
  var confirm = ui.alert(
    'Konfirmasi Refresh Data',
    'Apakah Anda yakin ingin memperbarui seluruh data di spreadsheet dengan data resmi terbaru dari ZAPIN?\n\nPerhatian: Baris data mulai baris ' + CONFIG.DATA_START_ROW + ' akan diselaraskan persis dengan database server ZAPIN.',
    ui.ButtonSet.YES_NO
  );
  if (confirm !== ui.Button.YES) return;

  var lock = LockService.getScriptLock();
  try {
    lock.waitLock(30000);
    PropertiesService.getScriptProperties().setProperty('IS_SYNCING', 'true');

    var response = UrlFetchApp.fetch(CONFIG.EXPORT_URL, {
      method: 'get',
      headers: { 'X-Zapin-Secret': CONFIG.SECRET_KEY },
      muteHttpExceptions: true
    });

    if (response.getResponseCode() !== 200) {
      PropertiesService.getScriptProperties().deleteProperty('IS_SYNCING');
      ui.alert('Gagal Mengambil Data', 'Server mengembalikan kode status HTTP ' + response.getResponseCode() + ':\n' + response.getContentText(), ui.ButtonSet.OK);
      return;
    }

    var result = JSON.parse(response.getContentText());
    if (result.status !== 'success' || !Array.isArray(result.data)) {
      PropertiesService.getScriptProperties().deleteProperty('IS_SYNCING');
      ui.alert('Format Respons Salah', 'Respons server tidak memuat data yang valid.', ui.ButtonSet.OK);
      return;
    }

    var items = result.data;
    var sheet = getTargetSheet();
    var lastRow = sheet.getLastRow();

    if (lastRow >= CONFIG.DATA_START_ROW) {
      var numRowsToClear = lastRow - CONFIG.DATA_START_ROW + 1;
      sheet.getRange(CONFIG.DATA_START_ROW, 2, numRowsToClear, 18).clearContent();
    }

    if (items.length > 0) {
      var rows = [];
      for (var i = 0; i < items.length; i++) {
        var it = items[i];
        rows.push([
          it.no_urut || (i + 1),
          it.nama_barang || '',
          it.merk || '',
          it.tipe || '',
          it.nomor_seri || '',
          it.tahun || '',
          it.jumlah || 1,
          it.cara_perolehan || '',
          it.nilai_perolehan || '',
          it.distributor || '',
          it.ruangan || '',
          it.lokasi_saat_ini || '',
          it.kondisi || '',
          it.aspak || 'TIDAK TERDATA',
          it.kib || 'TIDAK TERDATA',
          it.non_kib_dan_aspak || '',
          it.akl_akd || '',
          it.keterangan || ''
        ]);
      }

      var writeRange = sheet.getRange(CONFIG.DATA_START_ROW, 2, rows.length, 18);
      writeRange.setValues(rows);
      writeRange.setBorder(true, true, true, true, true, true, '#b7b7b7', SpreadsheetApp.BorderStyle.SOLID);
    }

    SpreadsheetApp.flush();
    PropertiesService.getScriptProperties().deleteProperty('IS_SYNCING');
    ui.alert('Sukses', 'Berhasil memperbarui ' + items.length + ' data alat kesehatan dari server ZAPIN.', ui.ButtonSet.OK);
  } catch (err) {
    PropertiesService.getScriptProperties().deleteProperty('IS_SYNCING');
    ui.alert('Terjadi Kesalahan', 'Gagal memproses refresh data: ' + err.toString(), ui.ButtonSet.OK);
  } finally {
    lock.releaseLock();
  }
}

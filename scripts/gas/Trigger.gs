/**
 * Handler Trigger Edit Real-Time (Google Spreadsheet -> Website ZAPIN).
 */

function onSheetEdit(e) {
  try {
    if (PropertiesService.getScriptProperties().getProperty('IS_SYNCING') === 'true') {
      return;
    }
    if (!e || !e.range) return;

    var range = e.range;
    var startRow = range.getRow();
    var numRows = range.getNumRows();
    var editedCol = range.getColumn();
    var sheet = range.getSheet();
    if (CONFIG.SHEET_NAME && sheet.getName() !== CONFIG.SHEET_NAME) {
      return;
    }
    
    var colName = '';
    for (var k in COLUMN_MAP) {
      if (COLUMN_MAP[k] === editedCol) {
        colName = k;
        break;
      }
    }
    
    for (var r = 0; r < numRows; r++) {
      var currentRow = startRow + r;
      if (currentRow < CONFIG.DATA_START_ROW) continue;
      
      var noUrut = parseInt(sheet.getRange(currentRow, COLUMN_MAP.no_urut).getValue(), 10);
      if (isNaN(noUrut) || noUrut <= 0) continue;
      
      var rowValues = sheet.getRange(currentRow, 1, 1, 19).getValues()[0];
      var payloadData = { no_urut: noUrut };
      
      for (var field in COLUMN_MAP) {
        if (field === 'no_urut') continue;
        payloadData[field] = rowValues[COLUMN_MAP[field] - 1];
      }
      
      UrlFetchApp.fetch(CONFIG.API_URL, {
        method: 'post',
        contentType: 'application/json',
        headers: { 'X-Zapin-Secret': CONFIG.SECRET_KEY },
        payload: JSON.stringify({
          secret: CONFIG.SECRET_KEY,
          action: 'sheet_row_edited',
          row_index: currentRow,
          edited_column_index: editedCol,
          edited_column_name: colName,
          old_value: e.oldValue || null,
          new_value: e.value || null,
          data: payloadData
        }),
        muteHttpExceptions: true
      });
    }
  } catch (err) {
    Logger.log('onSheetEdit error: ' + err.toString());
  }
}

/**
 * Handler Trigger Perubahan Struktur (Hapus Baris dari Spreadsheet -> ZAPIN).
 */
function onSheetChange(e) {
  try {
    if (PropertiesService.getScriptProperties().getProperty('IS_SYNCING') === 'true') {
      return;
    }
    if (!e || e.changeType !== 'REMOVE_ROW') {
      return;
    }
    syncActiveRowsToZapin();
  } catch (err) {
    Logger.log('onSheetChange error: ' + err.toString());
  }
}

/**
 * Mengirim daftar no_urut aktif ke ZAPIN untuk sinkronisasi penghapusan.
 */
function syncActiveRowsToZapin() {
  var sheet = getTargetSheet();
  var lastRow = sheet.getLastRow();
  var activeNoUruts = [];
  
  if (lastRow >= CONFIG.DATA_START_ROW) {
    var count = lastRow - CONFIG.DATA_START_ROW + 1;
    var values = sheet.getRange(CONFIG.DATA_START_ROW, COLUMN_MAP.no_urut, count, 1).getValues();
    for (var i = 0; i < values.length; i++) {
      var n = parseInt(values[i][0], 10);
      if (!isNaN(n) && n > 0) {
        activeNoUruts.push(n);
      }
    }
  }
  
  var response = UrlFetchApp.fetch(CONFIG.API_URL, {
    method: 'post',
    contentType: 'application/json',
    headers: { 'X-Zapin-Secret': CONFIG.SECRET_KEY },
    payload: JSON.stringify({
      secret: CONFIG.SECRET_KEY,
      action: 'reconcile_active_rows',
      active_no_uruts: activeNoUruts
    }),
    muteHttpExceptions: true
  });
  
  return response;
}


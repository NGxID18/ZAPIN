function doPost(e) {
  var lock = LockService.getScriptLock();
  try {
    lock.waitLock(10000);
    
    var json = JSON.parse(e.postData.contents);
    if (!json.secret || json.secret !== CONFIG.SECRET_KEY) {
      return jsonResponse({ status: 'error', message: 'Unauthorized' });
    }
    
    var sheet = getTargetSheet();
    var lastRow = sheet.getLastRow();

    if (json.action === 'delete_row') {
      var delNo = parseInt(json.no_urut, 10);
      if (isNaN(delNo)) {
        return jsonResponse({ status: 'error', message: 'no_urut wajib disertakan' });
      }
      if (lastRow >= CONFIG.DATA_START_ROW) {
        var count = lastRow - CONFIG.DATA_START_ROW + 1;
        var nos = sheet.getRange(CONFIG.DATA_START_ROW, COLUMN_MAP.no_urut, count, 1).getValues();
        for (var d = 0; d < nos.length; d++) {
          if (parseInt(nos[d][0], 10) === delNo) {
            var rowToDelete = CONFIG.DATA_START_ROW + d;
            PropertiesService.getScriptProperties().setProperty('IS_SYNCING', 'true');
            sheet.deleteRow(rowToDelete);
            SpreadsheetApp.flush();
            PropertiesService.getScriptProperties().deleteProperty('IS_SYNCING');
            return jsonResponse({
              status: 'success',
              message: 'Baris no_urut ' + delNo + ' (baris ' + rowToDelete + ') berhasil dihapus dari Google Spreadsheet.'
            });
          }
        }
      }
      return jsonResponse({ status: 'not_found', message: 'No. urut ' + delNo + ' tidak ditemukan di Google Spreadsheet.' });
    }
    
    if (json.action === 'batch_update_rows' && Array.isArray(json.items)) {
      var results = [];
      PropertiesService.getScriptProperties().setProperty('IS_SYNCING', 'true');
      for (var b = 0; b < json.items.length; b++) {
        var res = writeOrUpdateSingleRow(sheet, json.items[b]);
        results.push(res);
      }
      SpreadsheetApp.flush();
      PropertiesService.getScriptProperties().deleteProperty('IS_SYNCING');
      return jsonResponse({
        status: 'success',
        message: json.items.length + ' baris berhasil disinkronkan secara batch.',
        results: results
      });
    }

    if (json.action === 'renumber_all_rows') {
      var numRows = lastRow - CONFIG.DATA_START_ROW + 1;
      if (numRows > 0) {
        var numbers = [];
        for (var n = 1; n <= numRows; n++) {
          numbers.push([n]);
        }
        PropertiesService.getScriptProperties().setProperty('IS_SYNCING', 'true');
        sheet.getRange(CONFIG.DATA_START_ROW, COLUMN_MAP.no_urut, numRows, 1).setValues(numbers);
        SpreadsheetApp.flush();
        PropertiesService.getScriptProperties().deleteProperty('IS_SYNCING');
        return jsonResponse({
          status: 'success',
          message: 'Nomor urut kolom B berhasil diperbaiki berurutan dari 1 s/d ' + numRows + '.',
          total: numRows
        });
      }
      return jsonResponse({ status: 'error', message: 'Tidak ada baris data untuk diperbaiki.' });
    }

    var alkesData = json.data || json;
    var targetNoUrut = parseInt(alkesData.no_urut, 10);
    if (isNaN(targetNoUrut)) {
      return jsonResponse({ status: 'error', message: 'no_urut wajib disertakan' });
    }

    PropertiesService.getScriptProperties().setProperty('IS_SYNCING', 'true');
    var result = writeOrUpdateSingleRow(sheet, alkesData);
    SpreadsheetApp.flush();
    PropertiesService.getScriptProperties().deleteProperty('IS_SYNCING');

    return jsonResponse({
      status: 'success',
      message: 'Baris ' + result.row + ' berhasil diperbarui.',
      row: result.row,
      no_urut: targetNoUrut
    });
  } catch (err) {
    PropertiesService.getScriptProperties().deleteProperty('IS_SYNCING');
    return jsonResponse({ status: 'error', message: err.toString() });
  } finally {
    lock.releaseLock();
  }
}

function writeOrUpdateSingleRow(sheet, alkesData) {
  var lastRow = sheet.getLastRow();
  var targetNoUrut = parseInt(alkesData.no_urut, 10);
  if (isNaN(targetNoUrut)) return { error: 'invalid_no_urut' };

  var matchedRow = -1;
  if (lastRow >= CONFIG.DATA_START_ROW) {
    var numRows = lastRow - CONFIG.DATA_START_ROW + 1;
    var noValues = sheet.getRange(CONFIG.DATA_START_ROW, COLUMN_MAP.no_urut, numRows, 1).getValues();
    for (var i = 0; i < noValues.length; i++) {
      if (parseInt(noValues[i][0], 10) === targetNoUrut) {
        matchedRow = CONFIG.DATA_START_ROW + i;
        break;
      }
    }
  }

  var isNewRow = false;
  if (matchedRow === -1) {
    isNewRow = true;
    matchedRow = Math.max(lastRow + 1, CONFIG.DATA_START_ROW);
    sheet.getRange(matchedRow, COLUMN_MAP.no_urut).setValue(targetNoUrut);
  }

  var prevRow = matchedRow - 1;
  if (prevRow >= CONFIG.DATA_START_ROW) {
    var srcRange = sheet.getRange(prevRow, 2, 1, 18);
    var dstRange = sheet.getRange(matchedRow, 2, 1, 18);
    var hasValidation = sheet.getRange(matchedRow, COLUMN_MAP.kib).getDataValidation() !== null;
    if (isNewRow || !hasValidation) {
      srcRange.copyTo(dstRange, SpreadsheetApp.CopyPasteType.PASTE_FORMAT, false);
      srcRange.copyTo(dstRange, SpreadsheetApp.CopyPasteType.PASTE_DATA_VALIDATION, false);
    }
  }

  sheet.getRange(matchedRow, 2, 1, 18)
    .setBorder(true, true, true, true, true, true, '#b7b7b7', SpreadsheetApp.BorderStyle.SOLID);

  for (var key in COLUMN_MAP) {
    if (key === 'no_urut') continue;
    if (alkesData.hasOwnProperty(key) && alkesData[key] !== undefined) {
      var val = alkesData[key];
      sheet.getRange(matchedRow, COLUMN_MAP[key]).setValue(val === null ? '' : val);
    }
  }

  return { row: matchedRow, no_urut: targetNoUrut };
}

function doGet() {
  return jsonResponse({
    status: 'success',
    message: 'ZAPIN Sync Engine aktif',
    timestamp: new Date().toISOString()
  });
}

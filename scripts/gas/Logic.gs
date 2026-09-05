/**
 * Link URL Laporan Google Looker Studio / Data Studio
 */
const DATA_STUDIO_URL = "https://datastudio.google.com/reporting/5024dd9b-a5c5-46b5-b914-638c03f7acd8";

/**
 * Helper untuk mendapatkan atau membuat Sheet berdasarkan nama
 */
function getOrCreateSheet_(name) {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  let sheet = ss.getSheetByName(name);
  if (!sheet) {
    sheet = ss.insertSheet(name);
  }
  return sheet;
}

/**
 * Menu Utama pada Google Spreadsheet (ZAPIN)
 */
function onOpen() {
  try {
    const ui = SpreadsheetApp.getUi();
    ui.createMenu("ZAPIN")
      .addItem("Refresh Semua Data", "refreshAllSheetsManual")
      .addSeparator()
      .addItem("Refresh Data Alkes", "refreshOnlyAlkes")
      .addItem("Refresh Data Perbaikan", "refreshOnlyPerbaikan")
      .addItem("Refresh Data Kalibrasi", "refreshOnlyKalibrasi")
      .addSeparator()
      .addItem("Tambah Alkes", "submitTambahAlkes")
      .addSeparator()
      .addItem("Data Studio", "openDataStudio")
      .addToUi();
  } catch (e) {
    Logger.log("onOpen: " + e.toString());
  }
}

/**
 * Event Trigger Otomatis: Memfilter baris tabel saat Dropdown Bulan/Tahun di Baris 1 dipilih
 */
function onEdit(e) {
  try {
    if (!e || !e.range) return;
    const sheet = e.range.getSheet();
    const sheetName = sheet.getName();
    const row = e.range.getRow();
    const col = e.range.getColumn();

    // 1. Sheet Perbaikan (Dropdown Bulan di P1 / col 16, Tahun di Q1 / col 17)
    if (sheetName === "Perbaikan" && row === 1 && (col === 16 || col === 17)) {
      const selectedMonth = String(sheet.getRange(1, 16).getValue() || "SEMUA BULAN").trim();
      const selectedYear = String(sheet.getRange(1, 17).getValue() || "SEMUA TAHUN").trim();
      filterSheetByMonthAndYear_(sheet, 7, 8, selectedMonth, selectedYear);
    }

    // 2. Sheet Kalibrasi (Dropdown Bulan di O1 / col 15, Tahun di P1 / col 16)
    if (sheetName === "Kalibrasi" && row === 1 && (col === 15 || col === 16)) {
      const selectedMonth = String(sheet.getRange(1, 15).getValue() || "SEMUA BULAN").trim();
      const selectedYear = String(sheet.getRange(1, 16).getValue() || "SEMUA TAHUN").trim();
      filterSheetByMonthAndYear_(sheet, 9, 10, selectedMonth, selectedYear);
    }
  } catch (err) {
    Logger.log("onEdit filter error: " + err.toString());
  }
}

/**
 * Menyembunyikan / Menampilkan baris berdasarkan pilihan Bulan (12 Bulan) dan Tahun
 */
function filterSheetByMonthAndYear_(sheet, monthCol, dateCol, selectedMonth, selectedYear) {
  const lastRow = sheet.getLastRow();
  if (lastRow < 2) return;

  // Unhide semua baris data
  sheet.showRows(2, lastRow - 1);

  const isAllMonth = (!selectedMonth || selectedMonth === "SEMUA BULAN");
  const isAllYear = (!selectedYear || selectedYear === "SEMUA TAHUN");

  if (isAllMonth && isAllYear) {
    return;
  }

  const monthValues = sheet.getRange(2, monthCol, lastRow - 1, 1).getValues();
  const dateValues = sheet.getRange(2, dateCol, lastRow - 1, 1).getValues();

  for (let i = 0; i < monthValues.length; i++) {
    const monthText = String(monthValues[i][0]).toLowerCase();
    const dateText = String(dateValues[i][0]).toLowerCase();

    let matchMonth = isAllMonth;
    if (!isAllMonth) {
      matchMonth = (monthText.indexOf(selectedMonth.toLowerCase()) !== -1) ||
                   (dateText.indexOf(selectedMonth.toLowerCase()) !== -1);
    }

    let matchYear = isAllYear;
    if (!isAllYear) {
      matchYear = (monthText.indexOf(selectedYear) !== -1) ||
                  (dateText.indexOf(selectedYear) !== -1);
    }

    if (!matchMonth || !matchYear) {
      sheet.hideRows(2 + i);
    }
  }
}

/**
 * Pilihan: Data Studio Shortcut
 */
function openDataStudio() {
  const html = HtmlService.createHtmlOutput(
    '<!DOCTYPE html>' +
    '<html>' +
    '<head>' +
    '<base target="_blank">' +
    '<style>' +
    'body { font-family: Inter, sans-serif; text-align: center; padding: 25px 15px; margin: 0; background-color: #f8fafc; }' +
    'h3 { color: #064e3b; margin: 0 0 10px 0; font-size: 16px; }' +
    'p { color: #475569; font-size: 12px; margin-bottom: 20px; line-height: 1.5; }' +
    '.btn { background-color: #064e3b; color: #ffffff !important; padding: 10px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 13px; display: inline-block; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }' +
    '.btn:hover { background-color: #04382a; }' +
    '</style>' +
    '</head>' +
    '<body>' +
    '<h3>Dashboard Data Studio</h3>' +
    '<p>Jika dashboard tidak terbuka otomatis, klik tombol di bawah untuk membuka:</p>' +
    '<a href="' + DATA_STUDIO_URL + '" class="btn" target="_blank">Buka Dashboard Data Studio</a>' +
    '<script>' +
    'window.open("' + DATA_STUDIO_URL + '", "_blank");' +
    '</script>' +
    '</body>' +
    '</html>'
  ).setWidth(380).setHeight(170);

  SpreadsheetApp.getUi().showModalDialog(html, "Membuka Data Studio");
}

/**
 * 1. Refresh SEMUA Sheet (Alkes, Perbaikan, Kalibrasi)
 */
function refreshAllSheets(showAlertFlag) {
  const ss = SpreadsheetApp.getActiveSpreadsheet();

  const currentSheet = ss.getActiveSheet();
  const currentSheetName = currentSheet ? currentSheet.getName() : "Alkes";

  const alkesSheet = getOrCreateSheet_("Alkes");
  const perbaikanSheet = getOrCreateSheet_("Perbaikan");
  const kalibrasiSheet = getOrCreateSheet_("Kalibrasi");

  let inputSheet = ss.getSheetByName("Tambah Alkes");
  if (!inputSheet) {
    inputSheet = ss.insertSheet("Tambah Alkes");
    setupInputAlkesSheet(inputSheet);
  }

  const allData = fetchAllZapinData();
  if (!allData) return;

  if (allData.alkes) {
    renderAlkesViewSheet(alkesSheet, allData.alkes);
  }

  if (allData.pemeliharaan) {
    renderPerbaikanSheet(perbaikanSheet, allData.pemeliharaan);
  }

  if (allData.kalibrasi) {
    renderKalibrasiSheet(kalibrasiSheet, allData.kalibrasi);
  }

  const activeTarget = ss.getSheetByName(currentSheetName) || alkesSheet;
  ss.setActiveSheet(activeTarget);

  if (showAlertFlag) {
    showAlert_("Seluruh data (Alkes, Perbaikan, dan Kalibrasi) berhasil diperbarui dari database ZAPIN.");
  }
}

function refreshAllSheetsManual() {
  refreshAllSheets(true);
}

/**
 * 2. Refresh Khusus Sheet 'Alkes'
 */
function refreshOnlyAlkes() {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const currentSheet = ss.getActiveSheet();
  const currentSheetName = currentSheet ? currentSheet.getName() : "Alkes";

  const alkesSheet = getOrCreateSheet_("Alkes");
  const data = fetchZapinData();
  if (!data) return;

  renderAlkesViewSheet(alkesSheet, data);

  const activeTarget = ss.getSheetByName(currentSheetName) || alkesSheet;
  ss.setActiveSheet(activeTarget);

  showAlert_("Data pada Sheet 'Alkes' berhasil diperbarui.");
}

/**
 * 3. Refresh Khusus Sheet 'Perbaikan'
 */
function refreshOnlyPerbaikan() {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const currentSheet = ss.getActiveSheet();
  const currentSheetName = currentSheet ? currentSheet.getName() : "Perbaikan";

  const perbaikanSheet = getOrCreateSheet_("Perbaikan");
  const data = fetchPemeliharaanData();
  if (!data) return;

  renderPerbaikanSheet(perbaikanSheet, data);

  const activeTarget = ss.getSheetByName(currentSheetName) || perbaikanSheet;
  ss.setActiveSheet(activeTarget);

  showAlert_("Data pada Sheet 'Perbaikan' berhasil diperbarui.");
}

/**
 * 4. Refresh Khusus Sheet 'Kalibrasi'
 */
function refreshOnlyKalibrasi() {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const currentSheet = ss.getActiveSheet();
  const currentSheetName = currentSheet ? currentSheet.getName() : "Kalibrasi";

  const kalibrasiSheet = getOrCreateSheet_("Kalibrasi");
  const data = fetchKalibrasiData();
  if (!data) return;

  renderKalibrasiSheet(kalibrasiSheet, data);

  const activeTarget = ss.getSheetByName(currentSheetName) || kalibrasiSheet;
  ss.setActiveSheet(activeTarget);

  showAlert_("Data pada Sheet 'Kalibrasi' berhasil diperbarui.");
}

/**
 * Pilihan 5: Tambah Alkes
 */
function submitTambahAlkes() {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const inputSheet = getOrCreateSheet_("Tambah Alkes");
  const lastRow = inputSheet.getLastRow();

  if (lastRow < 2) {
    showAlert_("Form Tambah Alkes masih kosong. Silakan isi data alkes baru pada tabel di sheet Tambah Alkes.");
    return;
  }

  const numRows = lastRow - 1;
  const values = inputSheet.getRange(2, 1, numRows, 10).getValues();
  const payload = [];

  for (let i = 0; i < values.length; i++) {
    const rowData = values[i];
    const namaBarang = rowData[0];

    if (namaBarang && String(namaBarang).trim() !== "") {
      const cleanName = String(namaBarang).trim();
      const lowerName = cleanName.toLowerCase();
      if (lowerName === "nama barang" || lowerName.indexOf("nama barang") === 0 || lowerName.indexOf("ketik data") !== -1 || lowerName.indexOf("form tambah") !== -1) {
        continue;
      }

      payload.push({
        nama_barang: cleanName,
        merk: rowData[1] || "-",
        tipe: rowData[2] || "-",
        seri_number: rowData[3] || "-",
        tahun: rowData[4] || "-",
        ruang_pemilik: rowData[5] || "CSSD",
        lokasi_alkes: rowData[6] || "CSSD",
        kondisi: rowData[7] || "Baik",
        status_kalibrasi: rowData[8] || "BELUM DIKALIBRASI",
        keterangan: rowData[9] || "-"
      });
    }
  }

  if (payload.length === 0) {
    showAlert_("Tidak ada data alkes baru yang diisi. Pastikan kolom Nama Barang telah terisi.");
    return;
  }

  const result = pushZapinData(payload);

  if (result && result.status === "success") {
    setupInputAlkesSheet(inputSheet);
    refreshAllSheets(false);

    const viewSheet = getOrCreateSheet_("Alkes");
    ss.setActiveSheet(viewSheet);

    showAlert_("Sebanyak " + payload.length + " data alat kesehatan baru berhasil ditambahkan ke database ZAPIN.");
  }
}

/**
 * Trigger Otomatis Per 1 Jam
 */
function autoRefreshAlkes() {
  refreshAllSheets(false);
}

function showAlert_(message) {
  try {
    SpreadsheetApp.getUi().alert(message);
  } catch (e) {
    Logger.log(message);
  }
}

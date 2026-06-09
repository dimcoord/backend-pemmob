<?php
	$log_dir = __DIR__ . '/logs';
	$log_file = $log_dir . '/app.log';

	if (!is_dir($log_dir)) {
		mkdir($log_dir, 0777, true);
	}

	set_error_handler(function ($severity, $message, $file, $line) use ($log_file) {
		$entry = date('c') . " | ERROR | {$message} | {$file}:{$line}\n";
		error_log($entry, 3, $log_file);
		return true;
	});

	set_exception_handler(function ($exception) use ($log_file) {
		$entry = date('c') . " | EXCEPTION | {$exception->getMessage()} | {$exception->getFile()}:{$exception->getLine()}\n";
		error_log($entry, 3, $log_file);
	});

	register_shutdown_function(function () use ($log_file) {
		$error = error_get_last();
		if ($error) {
			$entry = date('c') . " | FATAL | {$error['message']} | {$error['file']}:{$error['line']}\n";
			error_log($entry, 3, $log_file);
		}
	});

require '../vendor/autoload.php';

include '../koneksi.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

$bulan = $_GET['bulan'];
$tahun = $_GET['tahun'];

$namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
	'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Title
$sheet->setCellValue('A1', "LAPORAN REKAP KAS");
$sheet->mergeCells('A1:E1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A2', strtoupper($namaBulan[(int)$bulan]) . " $tahun");
$sheet->mergeCells('A2:E2');
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// Header
$headers = ['No', 'Tanggal', 'Keterangan', 'Kas Masuk', 'Kas Keluar'];

$col = 'A';
$headerRow = 4;
foreach ($headers as $header) {
	$sheet->setCellValue($col . $headerRow, $header);
	$col++;
}

$headerStyle = $sheet->getStyle('A4:E4');
$headerStyle->getFont()->setBold(true);
$headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
$headerStyle->getFill()->getStartColor()->setARGB('FF0A192F');
$headerStyle->getFont()->getColor()->setARGB('FFFFFFFF');
$headerStyle->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// Data
$query = "SELECT tk.id, tk.tgl_transaksi, tk.tipe, tk.jumlah,
	COALESCE(tk.keterangan,
		CASE WHEN tk.tipe = 'Pemasukan' THEN CONCAT('Iuran ', a.nama) ELSE 'Pengeluaran' END
	) AS keterangan
	FROM transaksi_kas tk
	LEFT JOIN anggota a ON tk.anggota_id = a.id
	WHERE tk.tahun_iuran = '$tahun' AND tk.bulan_iuran = '$bulan'
	ORDER BY tk.tgl_transaksi ASC";

$hasil = mysqli_query($koneksi, $query);

if (!$hasil) {
	$entry = date('c') . " | DB_ERROR | " . mysqli_error($koneksi) . " | QUERY: " . $query . "\n";
	error_log($entry, 3, $log_file);
	echo "Error fetching data.";
	exit;
}

$row = 5;
$no = 1;
$totalMasuk = 0;
$totalKeluar = 0;

while ($data = mysqli_fetch_array($hasil)) {
	$col = 'A';
	$sheet->setCellValue($col++ . $row, $no);
	$sheet->setCellValue($col++ . $row, date('d/m/Y', strtotime($data['tgl_transaksi'])));

	$keterangan = $data['keterangan'];
	$sheet->setCellValue($col++ . $row, $keterangan);

	if ($data['tipe'] == 'Pemasukan') {
		$sheet->setCellValue($col . $row, $data['jumlah']);
		$sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$totalMasuk += $data['jumlah'];
		$col++;
		$sheet->setCellValue($col++ . $row, '-');
	} else {
		$sheet->setCellValue($col . $row, '-');
		$col++;
		$sheet->setCellValue($col . $row, $data['jumlah']);
		$sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
		$totalKeluar += $data['jumlah'];
		$col++;
	}

	$row++;
	$no++;
}

// Summary
$row++;
$saldo = $totalMasuk - $totalKeluar;

$sheet->setCellValue('C' . $row, 'Total Kas Masuk');
$sheet->getStyle('C' . $row)->getFont()->setBold(true);
$sheet->setCellValue('D' . $row, $totalMasuk);
$sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$sheet->getStyle('D' . $row)->getFont()->setBold(true);
$row++;

$sheet->setCellValue('C' . $row, 'Total Kas Keluar');
$sheet->getStyle('C' . $row)->getFont()->setBold(true);
$sheet->setCellValue('D' . $row, $totalKeluar);
$sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$sheet->getStyle('D' . $row)->getFont()->setBold(true);
$row++;

$sheet->setCellValue('C' . $row, 'Saldo Akhir');
$sheet->getStyle('C' . $row)->getFont()->setBold(true);
$sheet->setCellValue('D' . $row, $saldo);
$sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$sheet->getStyle('D' . $row)->getFont()->setBold(true)->setSize(13);

// Border all cells
$lastRow = $row;
$borderStyle = $sheet->getStyle('A4:E' . $lastRow);
$borderStyle->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

// Auto width
foreach (range('A', 'E') as $col) {
	$sheet->getColumnDimension($col)->setAutoSize(true);
}

if (ob_get_length()) ob_clean();

// Set header for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="REKAP_KAS_BULAN_' . $bulan . '_' . $tahun . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

?>

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

$tahun = $_GET['tahun'];

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Title
$sheet->setCellValue('A1', "REKAP IURAN ANGGOTA TAHUN $tahun");
$sheet->mergeCells('A1:O1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// Header
$headers = ['No', 'Nama', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des', 'Total'];

$col = 'A';
$headerRow = 3;
foreach ($headers as $header) {
	$sheet->setCellValue($col . $headerRow, $header);
	$col++;
}

$headerStyle = $sheet->getStyle('A3:O3');
$headerStyle->getFont()->setBold(true);
$headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
$headerStyle->getFill()->getStartColor()->setARGB('FF0A192F');
$headerStyle->getFont()->getColor()->setARGB('FFFFFFFF');
$headerStyle->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// Data
$query = "SELECT a.id, a.nama,
	COALESCE(SUM(CASE WHEN tk.bulan_iuran = 1 THEN tk.jumlah ELSE 0 END), 0) AS jan,
	COALESCE(SUM(CASE WHEN tk.bulan_iuran = 2 THEN tk.jumlah ELSE 0 END), 0) AS feb,
	COALESCE(SUM(CASE WHEN tk.bulan_iuran = 3 THEN tk.jumlah ELSE 0 END), 0) AS mar,
	COALESCE(SUM(CASE WHEN tk.bulan_iuran = 4 THEN tk.jumlah ELSE 0 END), 0) AS apr,
	COALESCE(SUM(CASE WHEN tk.bulan_iuran = 5 THEN tk.jumlah ELSE 0 END), 0) AS mei,
	COALESCE(SUM(CASE WHEN tk.bulan_iuran = 6 THEN tk.jumlah ELSE 0 END), 0) AS jun,
	COALESCE(SUM(CASE WHEN tk.bulan_iuran = 7 THEN tk.jumlah ELSE 0 END), 0) AS jul,
	COALESCE(SUM(CASE WHEN tk.bulan_iuran = 8 THEN tk.jumlah ELSE 0 END), 0) AS agu,
	COALESCE(SUM(CASE WHEN tk.bulan_iuran = 9 THEN tk.jumlah ELSE 0 END), 0) AS sep,
	COALESCE(SUM(CASE WHEN tk.bulan_iuran = 10 THEN tk.jumlah ELSE 0 END), 0) AS okt,
	COALESCE(SUM(CASE WHEN tk.bulan_iuran = 11 THEN tk.jumlah ELSE 0 END), 0) AS nov,
	COALESCE(SUM(CASE WHEN tk.bulan_iuran = 12 THEN tk.jumlah ELSE 0 END), 0) AS des
	FROM anggota a
	LEFT JOIN transaksi_kas tk ON a.id = tk.anggota_id AND tk.tahun_iuran = '$tahun' AND tk.tipe = 'Pemasukan'
	GROUP BY a.id, a.no_urut, a.nama
	ORDER BY a.nama ASC";

$hasil = mysqli_query($koneksi, $query);

if (!$hasil) {
	$entry = date('c') . " | DB_ERROR | " . mysqli_error($koneksi) . " | QUERY: " . $query . "\n";
	error_log($entry, 3, $log_file);
	echo "Error fetching data.";
	exit;
}

$row = 4;
$no = 1;

while ($data = mysqli_fetch_array($hasil)) {
	$col = 'A';
	$sheet->setCellValue($col++ . $row, $no);
	$sheet->setCellValue($col++ . $row, $data['nama']);

	$total = 0;
	for ($b = 1; $b <= 12; $b++) {
		$bulanKey = ['', 'jan', 'feb', 'mar', 'apr', 'mei', 'jun', 'jul', 'agu', 'sep', 'okt', 'nov', 'des'];
		$value = $data[$bulanKey[$b]];
		if ($value > 0) {
			$sheet->setCellValue($col . $row, $value);
			$total += $value;
		} else {
			$sheet->setCellValue($col . $row, '-');
		}
		$col++;
	}

	$sheet->setCellValue($col . $row, $total > 0 ? $total : '-');
	$row++;
	$no++;
}

// Border all cells
$lastRow = $row - 1;
$borderStyle = $sheet->getStyle('A3:O' . $lastRow);
$borderStyle->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

// Auto width
foreach (range('A', 'O') as $col) {
	$sheet->getColumnDimension($col)->setAutoSize(true);
}

if (ob_get_length()) ob_clean();

// Set header for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="REKAP_IURAN_TAHUN_' . $tahun . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

?>

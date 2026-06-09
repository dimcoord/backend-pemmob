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

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Title
$sheet->setCellValue('A1', "LAPORAN REKAP SIMPAN PINJAM");
$sheet->mergeCells('A1:H1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// Header
$headers = ['No', 'Nama', 'Simpanan Pokok', 'Simpanan Wajib', 'Total Simpanan', 'Total Pinjaman', 'Total Angsuran', 'Sisa Pinjaman'];

$col = 'A';
$headerRow = 3;
foreach ($headers as $header) {
	$sheet->setCellValue($col . $headerRow, $header);
	$col++;
}

$headerStyle = $sheet->getStyle('A3:H3');
$headerStyle->getFont()->setBold(true);
$headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
$headerStyle->getFill()->getStartColor()->setARGB('FF0A192F');
$headerStyle->getFont()->getColor()->setARGB('FFFFFFFF');
$headerStyle->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// Data
$query = "SELECT 
  a.id,
  a.no_urut,
  a.nama,
  COALESCE(SUM(CASE WHEN LOWER(s.jenis) = 'pokok' THEN s.jumlah ELSE 0 END), 0) AS simpanan_pokok,
  COALESCE(SUM(CASE WHEN LOWER(s.jenis) = 'wajib' THEN s.jumlah ELSE 0 END), 0) AS simpanan_wajib,
  COALESCE(SUM(s.jumlah), 0) AS total_simpanan,
  COALESCE(p.total_pinjaman, 0) AS total_pinjaman,
  COALESCE(ag.total_angsuran, 0) AS total_angsuran,
  COALESCE(p.total_pinjaman, 0) - COALESCE(ag.total_angsuran, 0) AS sisa_pinjaman
  FROM anggota a
  LEFT JOIN simpanan s ON a.id = s.anggota_id
  LEFT JOIN (
    SELECT anggota_id, SUM(jumlah_pinjam) AS total_pinjaman
    FROM pinjaman
    GROUP BY anggota_id
  ) p ON a.id = p.anggota_id
  LEFT JOIN (
    SELECT p2.anggota_id, SUM(ag2.jumlah_bayar) AS total_angsuran
    FROM angsuran ag2
    JOIN pinjaman p2 ON ag2.pinjaman_id = p2.id
    GROUP BY p2.anggota_id
  ) ag ON a.id = ag.anggota_id
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
	$sheet->setCellValue($col . $row, $data['simpanan_pokok']);
	$sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$col++;
	$sheet->setCellValue($col . $row, $data['simpanan_wajib']);
	$sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$col++;
	$sheet->setCellValue($col . $row, $data['total_simpanan']);
	$sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$col++;
	$sheet->setCellValue($col . $row, $data['total_pinjaman']);
	$sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$col++;
	$sheet->setCellValue($col . $row, $data['total_angsuran']);
	$sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$col++;
	$sheet->setCellValue($col . $row, $data['sisa_pinjaman']);
	$sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
	$col++;
	$row++;
	$no++;
}

// Border all cells
$lastRow = $row - 1;
$borderStyle = $sheet->getStyle('A3:H' . $lastRow);
$borderStyle->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

// Auto width
foreach (range('A', 'H') as $col) {
	$sheet->getColumnDimension($col)->setAutoSize(true);
}

if (ob_get_length()) ob_clean();

// Set header for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="REKAP_SIMPAN_PINJAM.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

?>

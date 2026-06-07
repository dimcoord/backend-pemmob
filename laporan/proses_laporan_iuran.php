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

	include '../koneksi.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$bulan = $_GET['bulan']; // format: YYYY-MM

$query = "SELECT a.id, a.no_urut, a.nama,
          CASE WHEN tk.id IS NOT NULL THEN 1 ELSE 0 END AS sudah_bayar,
          COALESCE(tk.jumlah, 0) AS jumlah
          FROM anggota a
          LEFT JOIN transaksi_kas tk 
            ON a.id = tk.anggota_id 
            AND tk.tipe = 'Pemasukan'
            AND tk.bulan_iuran = MONTH('$bulan-01')
            AND tk.tahun_iuran = YEAR('$bulan-01')
          ORDER BY sudah_bayar ASC, a.nama ASC";
$hasil = mysqli_query($koneksi, $query);

if (!$hasil) {
	$entry = date('c') . " | DB_ERROR | " . mysqli_error($koneksi) . " | QUERY: " . $query . "\n";
	error_log($entry, 3, $log_file);
	echo "Error fetching data.";
	exit;
}

$temp = [];

while($data = mysqli_fetch_array($hasil)){
	$temp[] = $data;
}

echo json_encode($temp);

?>

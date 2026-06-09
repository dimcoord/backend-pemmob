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

	$query = "SELECT SUM(
		CASE
			WHEN tipe = 'Pemasukan' THEN jumlah
			WHEN tipe = 'Pengeluaran' THEN -jumlah
			ELSE 0
		END
	) AS saldo
	FROM transaksi_kas";

	$hasil = mysqli_query($koneksi, $query);

	if (!$hasil) {
		$entry = date('c') . " | DB_ERROR | " . mysqli_error($koneksi) . " | QUERY: " . $query . "\n";
		error_log($entry, 3, $log_file);

		http_response_code(500);
		echo json_encode(['error' => 'Error fetching data']);
		exit;
	}

	$data = mysqli_fetch_assoc($hasil);

	echo json_encode($data);

?>
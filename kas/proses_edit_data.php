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

	$id = $_POST['dart_id'];
	$anggota_id = $_POST['dart_anggota_id'];
	$tgl_transaksi = $_POST['dart_tgl_transaksi'];
	$tipe = $_POST['dart_tipe'];
	$jumlah = $_POST['dart_jumlah'];
	$bulan_iuran = $_POST['dart_bulan_iuran'];
	$tahun_iuran = $_POST['dart_tahun_iuran'];
	
	$query = "UPDATE transaksi_kas SET anggota_id = '".$anggota_id."', tgl_transaksi = '".$tgl_transaksi."', tipe = '".$tipe."', jumlah = '".$jumlah."', bulan_iuran = '".$bulan_iuran."', tahun_iuran = '".$tahun_iuran."' WHERE id = '".$id."'";

	if (!mysqli_query($koneksi, $query)) {
		$entry = date('c') . " | DB_ERROR | " . mysqli_error($koneksi) . " | QUERY: " . $query . "\n";
		error_log($entry, 3, $log_file);
		echo "Error updating data.";
	}

?>
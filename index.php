<!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<title>Backend Pemmob API Documentation</title>
		<style>
			:root {
				color-scheme: light;
				--bg: #f7f7f2;
				--card: #ffffff;
				--text: #1a1a1a;
				--muted: #5a5a5a;
				--accent: #0b5d5f;
				--border: #e3e3dc;
				--code-bg: #f0f0ea;
			}
			* {
				box-sizing: border-box;
			}
			body {
				margin: 0;
				font-family: "Source Serif 4", "Times New Roman", serif;
				color: var(--text);
				background: linear-gradient(180deg, #f1efe6 0%, #f7f7f2 50%, #ffffff 100%);
			}
			header {
				padding: 48px 20px 28px;
				background: radial-gradient(circle at 20% 20%, #dfe8e3 0%, transparent 55%),
					radial-gradient(circle at 80% 0%, #e7d9cf 0%, transparent 50%);
				border-bottom: 1px solid var(--border);
			}
			main {
				max-width: 1000px;
				margin: 0 auto;
				padding: 28px 20px 60px;
			}
			h1 {
				margin: 0 0 6px;
				font-size: 2.2rem;
				color: var(--accent);
			}
			h2 {
				margin: 28px 0 10px;
				font-size: 1.4rem;
			}
			h3 {
				margin: 20px 0 8px;
				font-size: 1.1rem;
			}
			p {
				margin: 6px 0 12px;
				color: var(--muted);
				line-height: 1.5;
			}
			code,
			pre {
				font-family: "Fira Code", "Courier New", monospace;
				font-size: 0.95rem;
			}
			pre {
				background: var(--code-bg);
				padding: 12px 14px;
				border-radius: 10px;
				overflow-x: auto;
				border: 1px solid var(--border);
			}
			.card {
				background: var(--card);
				border: 1px solid var(--border);
				border-radius: 14px;
				padding: 16px 18px;
				margin-bottom: 18px;
				box-shadow: 0 10px 24px rgba(15, 15, 15, 0.05);
			}
			.endpoint {
				display: flex;
				flex-wrap: wrap;
				gap: 10px 16px;
				align-items: baseline;
			}
			.method {
				font-weight: 700;
				color: var(--accent);
			}
			.path {
				font-weight: 600;
			}
			ul {
				margin: 6px 0 12px 18px;
			}
			li {
				margin: 4px 0;
			}
			.note {
				background: #fef7e8;
				border: 1px solid #f2d7a7;
				padding: 10px 12px;
				border-radius: 10px;
				color: #6b4f1d;
			}
			@media (max-width: 700px) {
				header {
					padding: 36px 16px 22px;
				}
				main {
					padding: 22px 16px 48px;
				}
			}
		</style>
	</head>
	<body>
		<header>
			<div style="max-width: 1000px; margin: 0 auto;">
				<h1>Backend Pemmob API Documentation</h1>
				<p>Endpoints for CRUD operations across anggota, pinjaman, simpanan, kas, and laporan.</p>
			</div>
		</header>
		<main>
			<section class="card">
				<h2>Base URL</h2>
				<p>Assuming the app runs under Laragon:</p>
				<pre><code>http://localhost/backend-pemmob</code></pre>
				<p class="note">All request bodies are form-encoded (POST fields). None of the endpoints return a success payload; errors return a plain text message.</p>
			</section>

			<section class="card">
				<h2>Common Response Shape</h2>
				<p>List endpoints return a JSON array of rows. Each row is the raw result from <code>mysqli_fetch_array</code>, so it includes both numeric and associative keys.</p>
			</section>

			<section class="card">
				<h2>Anggota</h2>

				<div class="endpoint">
					<span class="method">GET</span>
					<span class="path">/anggota/proses_tampil_data.php</span>
				</div>
				<p>Returns all anggota ordered by <code>urut</code> descending.</p>
				<pre><code>curl -X GET "http://localhost/backend-pemmob/anggota/proses_tampil_data.php"</code></pre>

				<div class="endpoint">
					<span class="method">POST</span>
					<span class="path">/anggota/proses_tambah_data.php</span>
				</div>
				<p>Creates a new anggota.</p>
				<ul>
					<li><code>dart_urut</code> (string/number)</li>
					<li><code>dart_nama</code> (string)</li>
					<li><code>dart_alamat</code> (string)</li>
				</ul>

				<div class="endpoint">
					<span class="method">POST</span>
					<span class="path">/anggota/proses_edit_data.php</span>
				</div>
				<p>Updates an existing anggota.</p>
				<ul>
					<li><code>dart_id</code> (string/number)</li>
					<li><code>dart_urut</code> (string/number)</li>
					<li><code>dart_nama</code> (string)</li>
					<li><code>dart_alamat</code> (string)</li>
				</ul>

				<div class="endpoint">
					<span class="method">POST</span>
					<span class="path">/anggota/proses_hapus_data.php</span>
				</div>
				<p>Deletes an anggota.</p>
				<ul>
					<li><code>dart_id</code> (string/number)</li>
				</ul>
			</section>

			<section class="card">
				<h2>Pinjaman</h2>

				<div class="endpoint">
					<span class="method">GET</span>
					<span class="path">/pinjaman/proses_tampil_data.php</span>
				</div>
				<p>Returns all pinjaman ordered by <code>id</code> descending.</p>

				<div class="endpoint">
					<span class="method">POST</span>
					<span class="path">/pinjaman/proses_tambah_data.php</span>
				</div>
				<p>Creates a new pinjaman.</p>
				<ul>
					<li><code>dart_anggota_id</code> (string/number)</li>
					<li><code>dart_tgl_pinjam</code> (string, date)</li>
					<li><code>dart_jumlah_pinjam</code> (string/number)</li>
					<li><code>dart_status</code> (string)</li>
				</ul>

				<div class="endpoint">
					<span class="method">POST</span>
					<span class="path">/pinjaman/proses_edit_data.php</span>
				</div>
				<p>Updates an existing pinjaman.</p>
				<ul>
					<li><code>dart_id</code> (string/number)</li>
					<li><code>dart_anggota_id</code> (string/number)</li>
					<li><code>dart_tgl_pinjam</code> (string, date)</li>
					<li><code>dart_jumlah_pinjam</code> (string/number)</li>
					<li><code>dart_status</code> (string)</li>
				</ul>

				<div class="endpoint">
					<span class="method">POST</span>
					<span class="path">/pinjaman/proses_hapus_data.php</span>
				</div>
				<p>Deletes a pinjaman.</p>
				<ul>
					<li><code>dart_id</code> (string/number)</li>
				</ul>
			</section>

			<section class="card">
				<h2>Simpanan</h2>

				<div class="endpoint">
					<span class="method">GET</span>
					<span class="path">/simpanan/proses_tampil_data.php</span>
				</div>
				<p>Returns all simpanan ordered by <code>id</code> descending.</p>

				<div class="endpoint">
					<span class="method">POST</span>
					<span class="path">/simpanan/proses_tambah_data.php</span>
				</div>
				<p>Creates a new simpanan.</p>
				<ul>
					<li><code>dart_anggota_id</code> (string/number)</li>
					<li><code>dart_tgl_transaksi</code> (string, date)</li>
					<li><code>dart_jenis_simpanan</code> (string)</li>
					<li><code>dart_jumlah_simpanan</code> (string/number)</li>
					<li><code>dart_bulan_iuran</code> (string)</li>
					<li><code>dart_tahun_iuran</code> (string/number)</li>
				</ul>

				<div class="endpoint">
					<span class="method">POST</span>
					<span class="path">/simpanan/proses_edit_data.php</span>
				</div>
				<p>Updates an existing simpanan.</p>
				<ul>
					<li><code>dart_id</code> (string/number)</li>
					<li><code>dart_anggota_id</code> (string/number)</li>
					<li><code>dart_tgl_transaksi</code> (string, date)</li>
					<li><code>dart_jenis_simpanan</code> (string)</li>
					<li><code>dart_jumlah_simpanan</code> (string/number)</li>
					<li><code>dart_bulan_iuran</code> (string)</li>
					<li><code>dart_tahun_iuran</code> (string/number)</li>
				</ul>

				<div class="endpoint">
					<span class="method">POST</span>
					<span class="path">/simpanan/proses_hapus_data.php</span>
				</div>
				<p>Deletes a simpanan.</p>
				<ul>
					<li><code>dart_id</code> (string/number)</li>
				</ul>
			</section>

			<section class="card">
				<h2>Kas</h2>
				<p>These endpoints operate on the <code>pemmob_p12</code> table using form-style fields.</p>

				<div class="endpoint">
					<span class="method">GET</span>
					<span class="path">/kas/proses_tampil_data.php</span>
				</div>
				<p>Returns all kas entries ordered by <code>id</code> descending.</p>

				<div class="endpoint">
					<span class="method">POST</span>
					<span class="path">/kas/proses_tambah_data.php</span>
				</div>
				<p>Creates a new kas entry.</p>
				<ul>
					<li><code>dart_email</code> (string)</li>
					<li><code>dart_password</code> (string)</li>
					<li><code>dart_deskripsi</code> (string)</li>
					<li><code>dart_kepuasan</code> (string)</li>
					<li><code>dart_agree</code> (optional, any value sets 1)</li>
				</ul>

				<div class="endpoint">
					<span class="method">POST</span>
					<span class="path">/kas/proses_edit_data.php</span>
				</div>
				<p>Updates an existing kas entry.</p>
				<ul>
					<li><code>dart_id</code> (string/number)</li>
					<li><code>dart_email</code> (string)</li>
					<li><code>dart_password</code> (string)</li>
					<li><code>dart_deskripsi</code> (string)</li>
					<li><code>dart_kepuasan</code> (string)</li>
					<li><code>dart_agree</code> (optional, any value sets 1)</li>
				</ul>

				<div class="endpoint">
					<span class="method">POST</span>
					<span class="path">/kas/proses_hapus_data.php</span>
				</div>
				<p>Deletes a kas entry.</p>
				<ul>
					<li><code>dart_id</code> (string/number)</li>
				</ul>
			</section>

			<section class="card">
				<h2>Laporan</h2>
				<p>These endpoints also operate on the <code>pemmob_p12</code> table (same fields as kas).</p>

				<div class="endpoint">
					<span class="method">GET</span>
					<span class="path">/laporan/proses_tampil_data.php</span>
				</div>
				<p>Returns all laporan entries ordered by <code>id</code> descending.</p>

				<div class="endpoint">
					<span class="method">POST</span>
					<span class="path">/laporan/proses_tambah_data.php</span>
				</div>
				<p>Creates a new laporan entry.</p>
				<ul>
					<li><code>dart_email</code> (string)</li>
					<li><code>dart_password</code> (string)</li>
					<li><code>dart_deskripsi</code> (string)</li>
					<li><code>dart_kepuasan</code> (string)</li>
					<li><code>dart_agree</code> (optional, any value sets 1)</li>
				</ul>

				<div class="endpoint">
					<span class="method">POST</span>
					<span class="path">/laporan/proses_edit_data.php</span>
				</div>
				<p>Updates an existing laporan entry.</p>
				<ul>
					<li><code>dart_id</code> (string/number)</li>
					<li><code>dart_email</code> (string)</li>
					<li><code>dart_password</code> (string)</li>
					<li><code>dart_deskripsi</code> (string)</li>
					<li><code>dart_kepuasan</code> (string)</li>
					<li><code>dart_agree</code> (optional, any value sets 1)</li>
				</ul>

				<div class="endpoint">
					<span class="method">POST</span>
					<span class="path">/laporan/proses_hapus_data.php</span>
				</div>
				<p>Deletes a laporan entry.</p>
				<ul>
					<li><code>dart_id</code> (string/number)</li>
				</ul>
			</section>
		</main>
	</body>
</html>

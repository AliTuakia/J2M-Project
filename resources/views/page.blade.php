<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rating Produk | J2M Jok Motor</title>
    <style>
        :root { --red:#c51d2d; --dark:#1d1d1f; --muted:#707078; --line:#ececf0; --gold:#ffb400; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:Arial, Helvetica, sans-serif; color:var(--dark); background:#f7f7f8; }
        .container { width:min(1120px, calc(100% - 32px)); margin:auto; }
        header { background:#181818; color:#fff; padding:18px 0; }
        .nav { display:flex; justify-content:space-between; align-items:center; gap:20px; }
        .brand { font-size:22px; font-weight:800; letter-spacing:.5px; } .brand span { color:#ef3340; }
        .back { color:#fff; text-decoration:none; font-size:14px; }
        main { padding:42px 0 64px; }
        h1 { margin:0 0 8px; font-size:30px; } .subtitle { margin:0 0 24px; color:var(--muted); }
        .rating-card { display:grid; grid-template-columns:310px 1fr; background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 8px 28px rgba(0,0,0,.06); }
        .score { padding:36px 28px; background:linear-gradient(145deg,#d52233,#a91120); color:#fff; text-align:center; }
        .score small { display:block; font-size:14px; opacity:.85; } .number { font-size:76px; line-height:1; font-weight:800; margin:10px 0; }
        .stars { color:var(--gold); font-size:28px; letter-spacing:3px; white-space:nowrap; text-shadow:0 1px 1px rgba(0,0,0,.15); }
        .score p { margin:14px 0 0; font-size:14px; opacity:.9; }
        .details { padding:32px; } .details h2 { margin:0 0 22px; font-size:21px; }
        .row { display:grid; grid-template-columns:90px 1fr 48px; align-items:center; gap:12px; margin:14px 0; font-size:14px; }
        .bar { height:9px; background:#ececf0; border-radius:20px; overflow:hidden; } .fill { height:100%; background:var(--gold); border-radius:20px; }
        .review { margin-top:24px; padding:20px; background:#fff; border-radius:14px; border:1px solid var(--line); }
        .review-top { display:flex; justify-content:space-between; gap:12px; align-items:center; } .review h3 { margin:0; font-size:16px; }
        .review p { margin:10px 0 0; color:#5d5d65; line-height:1.55; font-size:14px; }
        .badge { color:#14804a; background:#e9f8ef; padding:6px 10px; border-radius:20px; font-size:12px; font-weight:700; }
        @media (max-width:680px) { .rating-card { grid-template-columns:1fr; } .score { padding:28px; } .details { padding:26px 20px; } h1 { font-size:25px; } }
    </style>
</head>
<body>
    <header>
        <div class="container nav">
            <div class="brand">J2M <span>JOK MOTOR</span></div>
            <a class="back" href="/">← Kembali ke Beranda</a>
        </div>
    </header>

    <main class="container">
        <h1>Rating Penjualan Produk</h1>
        <p class="subtitle">Penilaian pelanggan untuk produk jok motor J2M.</p>

        <section class="rating-card">
            <div class="score">
                <small>Rata-rata Penilaian</small>
                <div class="number">4.5</div>
                <div class="stars" aria-label="Rating 4.5 dari 5 bintang">★★★★<span style="color:#fff; opacity:.48">★</span></div>
                <p>Berdasarkan 128 ulasan pelanggan</p>
            </div>

            <div class="details">
                <h2>Distribusi Rating</h2>
                <div class="row"><span>5 Bintang</span><div class="bar"><div class="fill" style="width:78%"></div></div><strong>78%</strong></div>
                <div class="row"><span>4 Bintang</span><div class="bar"><div class="fill" style="width:16%"></div></div><strong>16%</strong></div>
                <div class="row"><span>3 Bintang</span><div class="bar"><div class="fill" style="width:4%"></div></div><strong>4%</strong></div>
                <div class="row"><span>2 Bintang</span><div class="bar"><div class="fill" style="width:1%"></div></div><strong>1%</strong></div>
                <div class="row"><span>1 Bintang</span><div class="bar"><div class="fill" style="width:1%"></div></div><strong>1%</strong></div>
            </div>
        </section>

        <article class="review">
            <div class="review-top">
                <div><h3>Andi Pratama <span class="stars" style="font-size:16px; letter-spacing:1px">★★★★★</span></h3></div>
                <span class="badge">Pembeli Terverifikasi</span>
            </div>
            <p>Jok motor nyaman, jahitan rapi, dan pengiriman cepat. Sesuai dengan deskripsi produk.</p>
        </article>
    </main>
</body>
</html>

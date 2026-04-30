<?php
$page_title = "القرآن الكريم";
include('header.php');

$verses = [
    ["بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ", "الفاتحة - 1"],
    ["الْحَمْدُ لِلَّهِ رَبِّ الْعَالَمِينَ", "الفاتحة - 2"],
    ["الرَّحْمَٰنِ الرَّحِيمِ", "الفاتحة - 3"],
    ["مَالِكِ يَوْمِ الدِّينِ", "الفاتحة - 4"]
];

$random_verse = $verses[array_rand($verses)];
?>

<div class="quran-verse">
    <h2>📖 آية عشوائية من القرآن</h2>
    <div class="arabic-text"><?php echo $random_verse[0]; ?></div>
    <p><?php echo $random_verse[1]; ?></p>
    <a href="#" class="btn">آية أخرى</a>
</div>

<div class="services-grid" style="margin-top: 40px;">
    <div class="service-card">
        <h3>سورة الفاتحة</h3>
        <p>أعظم سورة في القرآن</p>
        <a href="#" class="btn">اقرأ</a>
    </div>
    <div class="service-card">
        <h3>سورة الإخلاص</h3>
        <p>تعدل ثلث القرآن</p>
        <a href="#" class="btn">اقرأ</a>
    </div>
</div>

<?php include('footer.php'); ?>
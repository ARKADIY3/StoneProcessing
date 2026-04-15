<?php
// includes/footer.php - простой подвал, всегда внизу страницы
?>
<footer class="bg-dark text-white text-center py-3 mt-auto">
    <div class="container">
        <p class="mb-0">© <?= date('Y') ?> StoneProcessing - Продажа и обработка натурального камня</p>
    </div>
</footer>

<style>
    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
    }
    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }
    .mt-auto {
        margin-top: auto !important;
    }
</style>
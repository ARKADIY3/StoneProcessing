<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$stone_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stone = getStoneWithServices($pdo, $stone_id);

if (!$stone) {
    header('Location: index.php');
    exit();
}

$area_sqm = ($stone['length_cm'] * $stone['width_cm']) / 10000;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($stone['name']) ?> - StoneProcessing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stone-image { width: 100%; border-radius: 10px; }
        .price-large { font-size: 2rem; font-weight: bold; color: #28a745; }
        .service-card { border: 1px solid #ddd; border-radius: 8px; padding: 12px; margin-bottom: 10px; cursor: pointer; }
        .service-card.selected { border-color: #28a745; background: #e8f5e9; }
        .param-badge { background: #f8f9fa; padding: 6px 12px; border-radius: 8px; margin-right: 8px; display: inline-block; margin-bottom: 8px; }
    </style>
</head>
<body>
    <?php include 'menu.php'; ?>
    
    <div class="container my-5">
        <div class="row">
            <div class="col-md-6">
                <img src="../uploads/stones/<?= htmlspecialchars($stone['main_image'] ?? 'default_stone.png') ?>" class="stone-image" alt="<?= htmlspecialchars($stone['name']) ?>" onerror="this.src='../uploads/stones/default_stone.png'">
            </div>
            <div class="col-md-6">
                <h1><?= htmlspecialchars($stone['name']) ?></h1>
                
                <div class="my-3">
                    <span class="param-badge"><i class="fas fa-ruler"></i> Длина: <?= $stone['length_cm'] ?> см</span>
                    <span class="param-badge"><i class="fas fa-ruler-combined"></i> Ширина: <?= $stone['width_cm'] ?> см</span>
                    <span class="param-badge"><i class="fas fa-arrows-alt-v"></i> Высота: <?= $stone['height_cm'] ?> см</span>
                    <span class="param-badge"><i class="fas fa-weight-hanging"></i> Вес: <?= $stone['weight_kg'] ?> кг</span>
                    <span class="param-badge"><i class="fas fa-cube"></i> Объём: <?= $stone['volume_m3'] ?> м³</span>
                    <span class="param-badge"><i class="fas fa-palette"></i> Цвет: <?= htmlspecialchars($stone['color']) ?></span>
                    <span class="param-badge"><i class="fas fa-globe"></i> <?= htmlspecialchars($stone['origin']) ?></span>
                </div>
                
                <div class="mb-3">
                    <?php if($stone['quantity'] > 0): ?>
                        <span class="badge bg-success">В наличии: <?= $stone['quantity'] ?> шт.</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Нет в наличии</span>
                    <?php endif; ?>
                </div>
                
                <div class="price-large"><?= number_format($stone['price_per_sqm'], 2) ?> ₽/м²</div>
                <div class="text-muted mb-3">или <?= number_format($stone['price_per_unit'], 2) ?> ₽/шт</div>
                
                <?php if($stone['quantity'] > 0): ?>
                <form method="POST" action="cart.php" id="cartForm">
                    <input type="hidden" name="stone_id" value="<?= $stone['id'] ?>">
                    <div class="row mb-3">
                        <div class="col-4">
                            <label class="form-label">Количество (шт)</label>
                            <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?= $stone['quantity'] ?>" class="form-control">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Площадь (м²)</label>
                            <input type="text" class="form-control" value="<?= round($area_sqm, 2) ?>" disabled>
                        </div>
                    </div>
                    
                    <?php if(!empty($stone['available_services'])): ?>
                    <h5>Дополнительные услуги</h5>
                    <div id="servicesContainer">
                        <?php foreach($stone['available_services'] as $service): ?>
                        <div class="service-card" data-price="<?= $service['price_value'] ?>" data-price-type="<?= $service['price_type'] ?>">
                            <div class="form-check">
                                <input class="form-check-input service-checkbox" type="checkbox" name="services[]" value="<?= $service['id'] ?>" id="service_<?= $service['id'] ?>">
                                <label class="form-check-label" for="service_<?= $service['id'] ?>">
                                    <strong><?= htmlspecialchars($service['name']) ?></strong><br>
                                    <small><?= htmlspecialchars($service['description']) ?> — 
                                    <?= number_format($service['price_value'], 2) ?> 
                                    <?= $service['price_type'] == 'percentage' ? '%' : '₽' ?>
                                    <?= $service['unit'] ? "/{$service['unit']}" : '' ?></small>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-4 p-3 bg-light rounded">
                        <div class="d-flex justify-content-between">
                            <span>Стоимость камня:</span>
                            <span id="stoneTotal"><?= number_format($stone['price_per_unit'], 2) ?> ₽</span>
                        </div>
                        <div class="d-flex justify-content-between" id="servicesRow" style="display:none">
                            <span>Услуги:</span>
                            <span id="servicesTotal">0 ₽</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Итого:</strong>
                            <strong class="text-primary" id="grandTotal"><?= number_format($stone['price_per_unit'], 2) ?> ₽</strong>
                        </div>
                    </div>
                    
                    <button type="submit" name="add_to_cart" class="btn btn-warning btn-lg w-100 mt-3">Добавить в корзину</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mt-5">
            <div class="col-12">
                <ul class="nav nav-tabs">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#desc">Описание</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#specs">Характеристики</button></li>
                </ul>
                <div class="tab-content p-4 border">
                    <div class="tab-pane active" id="desc">
                        <p><?= nl2br(htmlspecialchars($stone['full_description'] ?: 'Описание отсутствует')) ?></p>
                    </div>
                    <div class="tab-pane" id="specs">
                        <table class="table">
                            <tr><th>Категория</th><td><?= htmlspecialchars($stone['category_name']) ?></td></tr>
                            <tr><th>Твёрдость по Моосу</th><td><?= $stone['hardness'] ?> / 10</td></tr>
                            <tr><th>Плотность</th><td><?= number_format($stone['density'], 0) ?> кг/м³</td></tr>
                            <tr><th>Вес</th><td><?= $stone['weight_kg'] ?> кг</td></tr>
                            <tr><th>Размеры</th><td><?= $stone['length_cm'] ?>×<?= $stone['width_cm'] ?>×<?= $stone['height_cm'] ?> см</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const quantityInput = document.getElementById('quantity');
        const basePrice = <?= $stone['price_per_unit'] ?>;
        const areaSqm = <?= $area_sqm ?>;
        
        function calculateTotal() {
            const quantity = parseInt(quantityInput.value) || 1;
            let stoneTotal = basePrice * quantity;
            let servicesTotal = 0;
            
            document.querySelectorAll('.service-checkbox:checked').forEach(cb => {
                const card = cb.closest('.service-card');
                const price = parseFloat(card.dataset.price);
                const priceType = card.dataset.priceType;
                
                if (priceType === 'fixed') servicesTotal += price;
                else if (priceType === 'per_sqm') servicesTotal += price * areaSqm * quantity;
                else if (priceType === 'percentage') servicesTotal += basePrice * (price / 100) * quantity;
            });
            
            document.getElementById('stoneTotal').textContent = stoneTotal.toFixed(2) + ' ₽';
            document.getElementById('servicesTotal').textContent = servicesTotal.toFixed(2) + ' ₽';
            document.getElementById('servicesRow').style.display = servicesTotal > 0 ? 'flex' : 'none';
            document.getElementById('grandTotal').textContent = (stoneTotal + servicesTotal).toFixed(2) + ' ₽';
        }
        
        quantityInput.addEventListener('input', calculateTotal);
        document.querySelectorAll('.service-checkbox').forEach(cb => {
            cb.addEventListener('change', function() {
                this.closest('.service-card').classList.toggle('selected', this.checked);
                calculateTotal();
            });
        });
        calculateTotal();
    </script>

<?php include '../includes/footer.php'; ?>

</body>
</html>
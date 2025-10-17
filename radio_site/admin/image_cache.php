<?php
// radio_site/admin/image_cache.php
require_once '../config/app.php';
require_once '../classes/ImageCacheManager.php';

$cacheManager = new ImageCacheManager();
$action = $_POST['action'] ?? '';

if ($action === 'cache_all') {
    $batchSize = intval($_POST['batch_size'] ?? 100);
    $results = $cacheManager->cacheAllHttpFavicons($batchSize);
    echo json_encode($results);
    exit;
}

if ($action === 'retry_failed') {
    $batchSize = intval($_POST['batch_size'] ?? 50);
    $results = $cacheManager->retryFailedDownloads($batchSize);
    echo json_encode($results);
    exit;
}

$stats = $cacheManager->getCacheStats();

include 'templates/header.php';
?>

<div class="header">
    <h1>🖼️ Кэширование Favicon'ов</h1>
    <p>Система кэширования HTTP изображений для iOS совместимости</p>
</div>

<div class="cache-stats">
    <h3>📊 Статистика</h3>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $stats['total_http_favicons'] ?></div>
            <div class="stat-label">Всего HTTP favicon'ов</div>
        </div>
        <?php foreach ($stats['stats'] as $stat): ?>
        <div class="stat-card stat-<?= $stat['status'] ?>">
            <div class="stat-value"><?= $stat['count'] ?></div>
            <div class="stat-label"><?= ucfirst($stat['status']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="cache-actions">
    <h3>⚡ Действия</h3>
    
    <div class="action-card">
        <h4>Загрузить все HTTP favicon'ы</h4>
        <p>Будут загружены все изображения, которые еще не в кэше</p>
        <div class="action-form">
            <label>Количество за раз: 
                <input type="number" id="batchSizeAll" value="100" min="1" max="500">
            </label>
            <button onclick="startCaching('all')" class="btn btn-primary">🚀 Запустить</button>
        </div>
        <div class="progress" id="progressAll" style="display: none;">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFillAll"></div>
            </div>
            <div class="progress-text" id="progressTextAll"></div>
        </div>
    </div>
    
    <div class="action-card">
        <h4>Повторить неудачные загрузки</h4>
        <p>Повторная попытка загрузки изображений с ошибками</p>
        <div class="action-form">
            <label>Количество за раз: 
                <input type="number" id="batchSizeFailed" value="50" min="1" max="200">
            </label>
            <button onclick="startCaching('failed')" class="btn btn-warning">🔄 Повторить</button>
        </div>
        <div class="progress" id="progressFailed" style="display: none;">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFillFailed"></div>
            </div>
            <div class="progress-text" id="progressTextFailed"></div>
        </div>
    </div>
</div>

<script>
let isProcessing = false;

function startCaching(type) {
    if (isProcessing) {
        alert('Процесс уже запущен');
        return;
    }
    
    const batchSize = type === 'all' ? 
        document.getElementById('batchSizeAll').value : 
        document.getElementById('batchSizeFailed').value;
    
    const progressEl = document.getElementById(`progress${type.charAt(0).toUpperCase() + type.slice(1)}`);
    const progressFill = document.getElementById(`progressFill${type.charAt(0).toUpperCase() + type.slice(1)}`);
    const progressText = document.getElementById(`progressText${type.charAt(0).toUpperCase() + type.slice(1)}`);
    
    progressEl.style.display = 'block';
    isProcessing = true;
    
    const formData = new FormData();
    formData.append('action', type === 'all' ? 'cache_all' : 'retry_failed');
    formData.append('batch_size', batchSize);
    
    fetch('image_cache.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        updateProgress(progressFill, progressText, data, type);
        isProcessing = false;
        
        // Обновляем страницу через секунду чтобы показать новую статистику
        setTimeout(() => {
            location.reload();
        }, 1000);
    })
    .catch(error => {
        console.error('Error:', error);
        progressText.innerHTML = '❌ Ошибка: ' + error.message;
        isProcessing = false;
    });
}

function updateProgress(progressFill, progressText, data, type) {
    const total = data.total;
    const success = data.success;
    const error = data.error;
    
    const percent = total > 0 ? (success / total) * 100 : 100;
    
    progressFill.style.width = percent + '%';
    progressText.innerHTML = `✅ ${success} успешно | ❌ ${error} ошибок | 📊 ${total} всего`;
}
</script>

<style>
.cache-stats {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.stat-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    border-left: 4px solid #007bff;
}

.stat-card.stat-success { border-left-color: #28a745; }
.stat-card.stat-error { border-left-color: #dc3545; }
.stat-card.stat-pending { border-left-color: #ffc107; }

.stat-value {
    font-size: 2em;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9em;
}

.cache-actions {
    background: white;
    padding: 20px;
    border-radius: 8px;
}

.action-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.action-form {
    display: flex;
    gap: 15px;
    align-items: center;
    margin-top: 15px;
}

.progress {
    margin-top: 15px;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745, #20c997);
    transition: width 0.3s ease;
}

.progress-text {
    margin-top: 5px;
    font-size: 0.9em;
    color: #6c757d;
}
</style>

<?php
include 'templates/footer.php';
?>
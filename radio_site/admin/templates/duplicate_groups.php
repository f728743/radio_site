<div class="header">
    <h1>Управление дубликатами радиостанций</h1>
    <p>Найдено групп с дубликатами: <?php echo $totalGroups; ?></p>
    <p>Страница <?php echo $page; ?> из <?php echo $totalPages; ?></p>
</div>

<div class="global-actions">
    <button type="button" class="btn btn-success" onclick="banAllSelected()">
        Оставить все выбранные станции (забанить остальные)
    </button>
</div>

<div id="loading" class="loading">
    <p>Обработка запроса...</p>
</div>

<?php if (empty($duplicateGroups)): ?>
    <div class="header">
        <p>Дубликатов не найдено или все дубликаты уже обработаны.</p>
    </div>
<?php else: ?>
    <?php foreach ($duplicateGroups as $group): ?>
    <div class="group" data-url="<?php echo htmlspecialchars($group['url']); ?>">
        <div class="group-header">
            <h3>URL: <span class="group-url"><?php echo htmlspecialchars($group['url']); ?></span></h3>
            <p>Дубликатов: <?php echo $group['duplicate_count']; ?></p>
        </div>

        <div class="group-actions">
            <button type="button" class="btn btn-primary" onclick="banGroupDuplicates(this)">
                Оставить выбранную станцию
            </button>
        </div>
        
        <?php foreach ($group['stations'] as $index => $station): ?>
        <div class="station <?php echo $index === 0 ? 'selected' : ''; ?>" data-station-uuid="<?php echo $station['stationuuid']; ?>">
            <input type="radio" 
                   name="station_<?php echo md5($group['url']); ?>" 
                   value="<?php echo $station['stationuuid']; ?>" 
                   class="station-radio"
                   <?php echo $index === 0 ? 'checked' : ''; ?>
                   onchange="selectStation(this)">
            
            <?php if (!empty($station['favicon'])): ?>
            <img src="<?php echo htmlspecialchars($station['favicon']); ?>" 
                 alt="Favicon" 
                 class="station-favicon"
                 onerror="this.style.display='none'">
            <?php endif; ?>
            
            <div class="station-info">
                <div class="station-name"><?php echo htmlspecialchars($station['name']); ?></div>
                <div class="station-details">
                    <?php echo htmlspecialchars($station['country'] ?? ''); ?>
                    <?php if (!empty($station['language'])) echo ' • ' . htmlspecialchars($station['language']); ?>
                    <?php if (!empty($station['codec'])) echo ' • ' . htmlspecialchars($station['codec']); ?>
                    <?php if (!empty($station['bitrate'])) echo ' • ' . $station['bitrate'] . ' kbps'; ?>
                    <?php echo $station['lastcheckok'] ? ' • ✅ Работает' : ' • ❌ Не работает'; ?>
                </div>
                <div class="station-stats">
                    <?php if (!empty($station['votes'])): ?>
                    <span class="stat votes">Голосов: <?php echo $station['votes']; ?></span>
                    <?php endif; ?>
                    <?php if (!empty($station['clickcount'])): ?>
                    <span class="stat clicks">Кликов: <?php echo $station['clickcount']; ?></span>
                    <?php endif; ?>
                    <?php if (!empty($station['clicktrend'])): ?>
                    <span class="stat trend">Тренд: <?php echo $station['clicktrend']; ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
    </div>
    <?php endforeach; ?>
    
    <?php include 'templates/pagination.php'; ?>
<?php endif; ?>

<div class="global-actions">
    <button type="button" class="btn btn-success" onclick="banAllSelected()">
        Оставить все выбранные станции (забанить остальные)
    </button>
</div>
<?php
require_once 'config/app.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Radio Stations - API Test</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>📻 Radio Station API Test</h1>
            <p>Тестирование поиска радиостанций для мобильного приложения</p>
            <div class="nav-links">
                <a href="admin" class="nav-link">Админка</a>
            </div>
        </header>

        <div class="search-section">
            <div class="search-form">
                <input type="text" id="searchQuery" placeholder="Введите rock, jazz, news, russian..." class="search-input">
                <select id="searchType" class="search-select">
                    <option value="name">По имени</option>
                    <option value="tag">По тегу</option>
                    <option value="language">По языку</option>
                </select>
                <button onclick="performSearch()" class="search-btn">🔍 Поиск</button>
            </div>
            
            <div class="search-examples">
                <strong>Быстрый поиск:</strong>
                <span class="example" onclick="setExample('rock')">🎸 Rock</span>
                <span class="example" onclick="setExample('jazz')">🎷 Jazz</span>
                <span class="example" onclick="setExample('russian')">🇷🇺 Russian</span>
                <span class="example" onclick="setExample('news')">📰 News</span>
                <span class="example" onclick="setExample('classical')">🎻 Classical</span>
            </div>
        </div>

        <div id="loading" class="loading" style="display: none;">
            <p>🔍 Ищем станции...</p>
        </div>

        <div id="results" class="results"></div>
        
        <div class="api-info">
            <h3>📚 API Endpoints:</h3>
            <div class="endpoints">
                <div class="endpoint">
                    <strong>GET</strong> <code>api/search?q=rock&type=name</code>
                    <span>Поиск станций</span>
                </div>
                <div class="endpoint">
                    <strong>GET</strong> <code>api/stations?uuid=station-uuid</code>
                    <span>Информация о станции</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Определяем переменные из PHP
        const baseUrl = '<?= BASE_URL ?>';
        const apiUrl = '<?= API_URL ?>';
        
        console.log('Base URL:', baseUrl);
        console.log('API URL:', apiUrl);

        function setExample(query) {
            document.getElementById('searchQuery').value = query;
            performSearch();
        }

        async function performSearch() {
            const query = document.getElementById('searchQuery').value.trim();
            const type = document.getElementById('searchType').value;
            
            if (!query) {
                alert('Пожалуйста, введите поисковый запрос');
                return;
            }
            
            const loading = document.getElementById('loading');
            const results = document.getElementById('results');
            
            loading.style.display = 'block';
            results.innerHTML = '';
            
            try {
                // Теперь baseUrl определена и содержит правильное значение
                const apiUrl = `/api/search?q=${encodeURIComponent(query)}&type=${type}`;
                console.log('🔍 API Request:', apiUrl);
                
                const response = await fetch(apiUrl);
                const data = await response.json();
                
                loading.style.display = 'none';
                
                if (data.success) {
                    console.log('✅ Search successful, results:', data.data.length);
                    displayResults(data.data);
                } else {
                    results.innerHTML = `<div class="error">❌ Ошибка API: ${data.error || 'Неизвестная ошибка'}</div>`;
                }
                
            } catch (error) {
                loading.style.display = 'none';
                console.error('❌ Search failed:', error);
                
                results.innerHTML = `
                    <div class="error">
                        <h4>❌ Ошибка поиска</h4>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        }

        function displayResults(stations) {
            const results = document.getElementById('results');
            
            if (stations.length === 0) {
                results.innerHTML = '<div class="no-results">😔 Станции не найдены</div>';
                return;
            }
            
            let html = `<h3>🎯 Найдено станций: ${stations.length}</h3>`;
            
            stations.forEach(station => {
                const favicon = station.favicon && station.favicon !== '' ? 
                    `<img src="${station.favicon}" alt="Favicon" class="station-favicon" onerror="this.style.display='none'">` : 
                    '<div class="station-favicon placeholder">📻</div>';
                
                html += `
                    <div class="station-card">
                        ${favicon}
                        <div class="station-info">
                            <div class="station-name">${escapeHtml(station.name || 'Без названия')}</div>
                            <div class="station-details">
                                ${station.country ? '📍 ' + station.country : ''} 
                                ${station.language ? ' • 🗣️ ' + station.language : ''}
                                ${station.codec ? ' • 🔊 ' + station.codec : ''}
                                ${station.bitrate ? ' • 📊 ' + station.bitrate + ' kbps' : ''}
                                ${station.lastcheckok ? ' • ✅ Работает' : ' • ❌ Не работает'}
                            </div>
                            <div class="station-stats">
                                ${station.votes ? `<span class="stat votes">👍 ${station.votes}</span>` : ''}
                                ${station.clickcount ? `<span class="stat clicks">👆 ${station.clickcount}</span>` : ''}
                                ${station.clicktrend ? `<span class="stat trend">📈 ${station.clicktrend}</span>` : ''}
                            </div>
                            ${station.tags ? `<div class="station-tags">🏷️ ${escapeHtml(station.tags)}</div>` : ''}
                        </div>
                    </div>
                `;
            });
            
            results.innerHTML = html;
        }

        function escapeHtml(unsafe) {
            if (!unsafe) return '';
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Обработка нажатия Enter
        document.getElementById('searchQuery').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    </script>
</body>
</html>
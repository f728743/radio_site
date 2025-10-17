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
                    <option value="tag">По тегу</option>
                    <option value="name">По имени</option>                    
                    <option value="language">По языку</option>
                </select>
                <button onclick="performSearch()" class="search-btn">🔍 Поиск</button>
            </div>
            
            <div class="search-examples">
                <strong>Быстрый поиск:</strong>
                <span class="example" onclick="setExample('rock')">🎸 Rock</span>
                <span class="example" onclick="setExample('jazz')">🎷 Jazz</span>
                <span class="example" onclick="setExample('talk')">🎙️ Talk</span>
                <span class="example" onclick="setExample('hiphop')">👑 Hip-Hop</span>                
                <span class="example" onclick="setExample('russian')">🇷🇺 Russian</span>                
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
                const apiUrl = `/api/search?q=${encodeURIComponent(query)}&type=${type}`;
                console.log('🔍 API Request:', apiUrl);
                
                const response = await fetch(apiUrl);
                const data = await response.json();
                
                loading.style.display = 'none';
                
                if (data.success) {
                    console.log('✅ Search successful:', {
                        real_stations: data.real_radio?.length || 0,
                        sim_series: data.sim_radio?.length || 0
                    });
                    displayResults(data);
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
                        <p style="font-size: 0.9em; margin-top: 10px;">Проверьте подключение к интернету</p>
                    </div>
                `;
            }
        }

        function displayResults(data) {
            const results = document.getElementById('results');
            
            // Проверяем что data - объект с real_radio и sim_radio
            const realStations = data.real_radio || [];
            const simStations = data.sim_radio || [];
            
            if (realStations.length === 0 && simStations.length === 0) {
                results.innerHTML = '<div class="no-results">😔 Станции не найдены</div>';
                return;
            }
            
            let html = '';
            let totalStations = realStations.length;

            // SIM радиостанции - ВЫВОДИМ ВВЕРХУ
            if (simStations.length > 0) {
                // Считаем общее количество SIM станций
                const totalSimStations = simStations.reduce((total, series) => total + series.stations.length, 0);
                
                html += `<h3>🎮 Найдено SIM радиостанций: ${totalSimStations} станций в ${simStations.length} сериях</h3>`;
                
                simStations.forEach(series => {
                    // Генерируем URL для картинки серии
                    const seriesImageUrl = generateImageUrl(series.url, series.logo);
                    
                    html += `
                        <div class="station-card">
                            <img src="${seriesImageUrl}" alt="${escapeHtml(series.title)}" class="station-favicon" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="station-favicon placeholder" style="display: none;">🎮</div>
                            <div class="station-info">
                                <div class="station-name">${escapeHtml(series.title)}</div>
                                <div class="station-details">
                                    Станций в серии: ${series.stations.length}
                                </div>
                                <div class="station-stations">
                    `;
                    
                    // Добавляем каждую станцию в серии
                    series.stations.forEach(station => {
                        // Генерируем URL для картинки станции (используем URL серии и logo станции)
                        const stationImageUrl = generateImageUrl(series.url, station.logo);
                        
                        html += `
                            <div class="sim-station">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                    <img src="${stationImageUrl}" alt="${escapeHtml(station.title)}" style="width: 32px; height: 32px; border-radius: 4px; object-fit: cover;" onerror="this.style.display='none'">
                                    <div style="font-weight: bold;">${escapeHtml(station.title)}</div>
                                </div>
                                ${station.tags ? `<div style="color: #666; font-size: 0.9em; margin-left: 42px;">🏷️ ${escapeHtml(station.tags)}</div>` : ''}
                            </div>
                        `;
                    });
                    
                    html += `
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
            
            // Реальные радиостанции - ВЫВОДИМ ПОСЛЕ SIM
            if (realStations.length > 0) {
                html += `<h3>🎯 Найдено реальных станций: ${realStations.length}</h3>`;
                
                realStations.forEach(station => {
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
            }
            
            results.innerHTML = html;
        }

        /**
         * Генерирует URL для картинки на основе URL серии и пути к логотипу
         * @param {string} seriesUrl - URL серии (с new_sim_radio_stations.json на конце)
         * @param {string} logoPath - Путь к логотипу из базы данных
         * @returns {string} Полный URL к изображению
         */
        function generateImageUrl(seriesUrl, logoPath) {
            if (!seriesUrl || !logoPath) {
                return '';
            }
            
            // Убираем "new_sim_radio_stations.json" из конца URL
            const baseUrl = seriesUrl.replace(/\/new_sim_radio_stations\.json$/, '');
            
            // Добавляем путь к логотипу и расширение .png
            return `${baseUrl}/${logoPath}.png`;
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
<?php
// radio_site/classes/ImageCacheManager.php

class ImageCacheManager {
    private $db;
    private $cacheDir;
    private $cacheUrl;
    private $timeout = 10;
    private $maxFileSize = 2097152; // 2MB
    
    public function __construct() {
        $this->db = getDatabaseConnection();
        $this->cacheDir = __DIR__ . '/../images/cache';
        $this->cacheUrl = BASE_URL . '/images/cache';
        
        // Создаем директорию если не существует
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
        
    /**
     * Проверяем нужно ли кэшировать URL
     */
    private function needsCaching($url) {
        return strpos($url, 'http://') === 0;
    }
    
    /**
     * Получить информацию о кэшированном изображении
     */
    private function getCachedImage($originalUrl) {
        $sql = "SELECT * FROM image_cache WHERE original_url = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$originalUrl]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Запустить процесс кэширования для всех HTTP favicon'ов
     */
    public function cacheAllHttpFavicons($batchSize = 100) {
        // Получаем станции с HTTP favicon'ами которые еще не в кэше или с ошибкой
        $sql = "
            SELECT rs.stationuuid, rs.favicon 
            FROM radio_stations rs 
            WHERE rs.favicon LIKE 'http://%' 
                AND rs.favicon NOT IN (
                    SELECT original_url FROM image_cache
                )
                AND rs.stationuuid NOT IN (
                    SELECT stationuuid FROM banned_stations
                )
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $batchSize, PDO::PARAM_INT);
        $stmt->execute();
        
        $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results = [
            'total' => count($stations),
            'success' => 0,
            'error' => 0,
            'pending' => 0
        ];
        
        foreach ($stations as $station) {
            $result = $this->downloadAndCache($station['favicon'], $station['stationuuid']);
            
            if ($result === true) {
                $results['success']++;
            } else {
                $results['error']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Скачивание и кэширование изображения
     */
    private function downloadAndCache($originalUrl, $stationUUID) {
        try {
            // Проверяем не кэшировали ли мы уже этот URL
            $existing = $this->getCachedImage($originalUrl);
            if ($existing) {
                if ($existing['status'] === 'success') {
                    return true; // Уже успешно кэширован
                }
                // Обновляем запись
                $this->updateCacheStatus($originalUrl, 'pending', null);
            } else {
                // Создаем новую запись
                $this->createCacheRecord($originalUrl, $stationUUID, 'pending');
            }
            
            // Скачиваем изображение
            $imageData = $this->downloadImage($originalUrl);
            if (!$imageData) {
                throw new Exception('Failed to download image');
            }
            
            // Проверяем MIME type
            $mimeType = $this->getImageMimeType($imageData);
            if (!$this->isValidImageType($mimeType)) {
                throw new Exception('Invalid image type: ' . $mimeType);
            }
            
            // Определяем расширение файла
            $extension = $this->getExtensionFromMimeType($mimeType);
            if (!$extension) {
                $extension = 'jpg'; // fallback
            }
            
            // Генерируем путь для сохранения
            $localPath = $this->generateLocalPath($originalUrl, $extension);
            $filePath = $this->cacheDir . '/' . $localPath;
            
            // Создаем поддиректории если нужно
            $dirPath = dirname($filePath);
            if (!is_dir($dirPath)) {
                mkdir($dirPath, 0755, true);
            }
            
            // Сохраняем файл
            if (file_put_contents($filePath, $imageData) === false) {
                throw new Exception('Failed to save image file');
            }
            
            // Обновляем запись в базе
            $this->updateCacheSuccess($originalUrl, $localPath, strlen($imageData), $mimeType);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Image cache error for {$originalUrl}: " . $e->getMessage());
            $this->updateCacheStatus($originalUrl, 'error', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Скачивание изображения
     */
    private function downloadImage($url) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => 'RadioStation Image Cache/1.0',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_BUFFERSIZE => 12800,
            CURLOPT_NOPROGRESS => false
        ]);
        
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_error($ch)) {
            throw new Exception('CURL error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200 || empty($data)) {
            throw new Exception("HTTP {$httpCode} or empty response");
        }
        
        if (strlen($data) > $this->maxFileSize) {
            throw new Exception("File too large: " . strlen($data) . " bytes");
        }
        
        return $data;
    }
    
    /**
     * Определение MIME типа изображения
     */
    private function getImageMimeType($imageData) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $imageData);
        finfo_close($finfo);
        
        return $mimeType;
    }
    
    /**
     * Проверка валидности типа изображения
     */
    private function isValidImageType($mimeType) {
        $allowedTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml'
        ];
        
        return in_array($mimeType, $allowedTypes);
    }
    
    /**
     * Получение расширения файла из MIME типа
     */
    private function getExtensionFromMimeType($mimeType) {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg'
        ];
        
        return $mimeMap[$mimeType] ?? null;
    }
    
    /**
     * Генерация локального пути для файла
     */
    private function generateLocalPath($originalUrl, $extension) {
        $hash = md5($originalUrl);
        $subdir = substr($hash, 0, 2);
        $filename = substr($hash, 2) . '.' . $extension;
        
        return $subdir . '/' . $filename;
    }
    
    /**
     * Создание записи в кэше
     */
    private function createCacheRecord($originalUrl, $stationUUID, $status) {
        $sql = "INSERT INTO image_cache (stationuuid, original_url, status) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$stationUUID, $originalUrl, $status]);
    }
    
    /**
     * Обновление статуса кэша
     */
    private function updateCacheStatus($originalUrl, $status, $errorMessage = null) {
        $sql = "UPDATE image_cache SET status = ?, error_message = ?, updated_at = NOW() WHERE original_url = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $errorMessage, $originalUrl]);
    }
    
    /**
     * Обновление при успешном кэшировании
     */
    private function updateCacheSuccess($originalUrl, $localPath, $fileSize, $mimeType) {
        $sql = "UPDATE image_cache SET status = 'success', local_path = ?, file_size = ?, mime_type = ?, error_message = NULL, updated_at = NOW() WHERE original_url = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$localPath, $fileSize, $mimeType, $originalUrl]);
    }
    
    /**
     * Получить статистику кэширования
     */
    public function getCacheStats() {
        $sql = "
            SELECT 
                status,
                COUNT(*) as count,
                SUM(file_size) as total_size
            FROM image_cache 
            GROUP BY status
        ";
        
        $stmt = $this->db->query($sql);
        $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Общее количество HTTP favicon'ов БЕЗ забаненных станций
        $sqlTotal = "
            SELECT COUNT(DISTINCT rs.favicon) as total 
            FROM radio_stations rs 
            WHERE rs.favicon LIKE 'http://%' 
            AND rs.favicon IS NOT NULL 
            AND rs.favicon != ''
            AND rs.stationuuid NOT IN (
                SELECT stationuuid FROM banned_stations
            )
        ";
        $totalHttp = $this->db->query($sqlTotal)->fetch(PDO::FETCH_ASSOC)['total'];
        
        return [
            'stats' => $stats,
            'total_http_favicons' => $totalHttp,
            'cached_count' => array_sum(array_column($stats, 'count'))
        ];
    }
    
    /**
     * Повторить загрузку failed изображений
     */
    public function retryFailedDownloads($batchSize = 50) {
        $sql = "
            SELECT ic.original_url, ic.stationuuid 
            FROM image_cache ic
            WHERE ic.status = 'error'
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $batchSize, PDO::PARAM_INT);
        $stmt->execute();
        
        $failed = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results = [
            'total' => count($failed),
            'success' => 0,
            'error' => 0
        ];
        
        foreach ($failed as $item) {
            $result = $this->downloadAndCache($item['original_url'], $item['stationuuid']);
            
            if ($result === true) {
                $results['success']++;
            } else {
                $results['error']++;
            }
        }
        
        return $results;
    }
}
?>
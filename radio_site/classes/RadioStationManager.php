<?php
require_once __DIR__ . '/../config/database.php';

class RadioStationManager {
    private $db;
    private $itemsPerPage = 300;
    private const REASON_DUPLICATE = 7;
    
    public function __construct() {
        $this->db = getDatabaseConnection();
    }
    
    public function getItemsPerPage() {
        return $this->itemsPerPage;
    }
    
    // Существующие методы для админки
    public function getDuplicateGroups($page = 1) {
        $offset = ($page - 1) * $this->itemsPerPage;
        
        $sql = "
            SELECT 
                rs.url,
                COUNT(*) as duplicate_count,
                GROUP_CONCAT(rs.stationuuid) as station_uuids
            FROM radio_stations rs
            WHERE rs.url IS NOT NULL 
                AND rs.url != ''
                AND rs.stationuuid NOT IN (
                    SELECT stationuuid FROM banned_stations WHERE reason_id = " . self::REASON_DUPLICATE . "
                )
            GROUP BY rs.url
            HAVING COUNT(*) > 1
            ORDER BY duplicate_count DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $this->itemsPerPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($groups as &$group) {
            $group['stations'] = $this->getNotBannedAsDuplicateStationsByUrl($group['url']);
        }
        
        return $groups;
    }
    
    public function getTotalDuplicateGroups() {
        $sql = "
            SELECT COUNT(*) as total
            FROM (
                SELECT url
                FROM radio_stations
                WHERE url IS NOT NULL AND url != ''
                AND stationuuid NOT IN (
                    SELECT stationuuid FROM banned_stations WHERE reason_id = " . self::REASON_DUPLICATE . "
                )
                GROUP BY url
                HAVING COUNT(*) > 1
            ) as duplicates
        ";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    private function getStationExcludingDuplicatesByUrl($url) {
        $sql = "
            SELECT 
                stationuuid, name, url, favicon, 
                votes, clickcount, clicktrend, country, language,
                bitrate, codec, lastcheckok
            FROM radio_stations 
            WHERE url = :url
                AND stationuuid NOT IN (
                    SELECT stationuuid FROM banned_stations WHERE reason_id = " . self::REASON_DUPLICATE . "
                )
            ORDER BY 
                COALESCE(votes, 0) DESC, 
                COALESCE(clickcount, 0) DESC,
                COALESCE(clicktrend, 0) DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':url', $url);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function banStations($stationUUIDs, $reason_id = self::REASON_DUPLICATE) {
        if (empty($stationUUIDs)) {
            error_log("No station UUIDs provided for banning");
            return 0;
        }
        
        // Логирование полученных UUID
        error_log("Banning stations: " . print_r($stationUUIDs, true));
        
        $placeholders = str_repeat('?,', count($stationUUIDs) - 1) . '?';
        $sql = "SELECT stationuuid, url FROM radio_stations WHERE stationuuid IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($stationUUIDs);
        $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Found stations to ban: " . count($stations));
        
        $insertSql = "
            INSERT INTO banned_stations (stationuuid, reason_id) 
            VALUES (?, ?)
        ";
        $insertStmt = $this->db->prepare($insertSql);
        
        $bannedCount = 0;
        foreach ($stations as $station) {
            try {
                $result = $insertStmt->execute([
                    $station['stationuuid'],
                    $reason_id
                ]);
                
                if ($result) {
                    $bannedCount++;
                    error_log("Successfully banned station: " . $station['stationuuid']);
                } else {
                    error_log("Failed to ban station: " . $station['stationuuid']);
                }
            } catch(PDOException $e) {
                error_log("Error banning station " . $station['stationuuid'] . ": " . $e->getMessage());
                continue;
            }
        }
        
        error_log("Total banned: " . $bannedCount);
        return $bannedCount;
    }
    
    // Новые методы для API поиска
    public function searchActiveStations($query, $type = 'name', $page = 1, $limit = 50) {
        $offset = ($page - 1) * $limit;
        
        $whereConditions = [
            'name' => "rs.name LIKE :query",
            'tag' => "rs.tags LIKE :query", 
            'language' => "rs.language LIKE :query"
        ];
        
        $condition = $whereConditions[$type] ?? $whereConditions['name'];
        
        $sql = "
            SELECT 
                rs.stationuuid, rs.name, rs.url, rs.favicon, 
                rs.votes, rs.clickcount, rs.clicktrend, rs.country, rs.language,
                rs.bitrate, rs.codec, rs.lastcheckok, rs.tags,
                -- Добавляем кэшированные favicon'ы через LEFT JOIN
                CASE 
                    WHEN ic.status = 'success' THEN CONCAT(:cacheBaseUrl, '/', ic.local_path)
                    ELSE rs.favicon
                END as cached_favicon,
                ic.status as cache_status
            FROM radio_stations rs
            LEFT JOIN image_cache ic ON (
                rs.favicon = ic.original_url 
                AND ic.status = 'success'
            )
            WHERE {$condition}
                AND rs.stationuuid NOT IN (
                    SELECT stationuuid FROM banned_stations
                )
            ORDER BY 
                COALESCE(rs.votes, 0) DESC, 
                COALESCE(rs.clickcount, 0) DESC
            LIMIT :limit OFFSET :offset
        ";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':query', '%' . $query . '%');
            $stmt->bindValue(':cacheBaseUrl', $this->getCacheBaseUrl());
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Database error in searchActiveStations: " . $e->getMessage());
            return [];
        }
    }

    public function getActiveStationByUUID($uuid) {
        $sql = "
            SELECT 
                rs.stationuuid, rs.name, rs.url, rs.favicon, 
                rs.votes, rs.clickcount, rs.clicktrend, rs.country, rs.language,
                rs.bitrate, rs.codec, rs.lastcheckok, rs.tags, rs.homepage,
                -- Добавляем кэшированные favicon'ы через LEFT JOIN
                CASE 
                    WHEN ic.status = 'success' THEN CONCAT(:cacheBaseUrl, '/', ic.local_path)
                    ELSE rs.favicon
                END as cached_favicon,
                ic.status as cache_status
            FROM radio_stations rs
            LEFT JOIN image_cache ic ON (
                rs.favicon = ic.original_url 
                AND ic.status = 'success'
            )
            WHERE rs.stationuuid = :uuid
                AND rs.stationuuid NOT IN (
                    SELECT stationuuid FROM banned_stations
                )
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uuid', $uuid);
        $stmt->bindValue(':cacheBaseUrl', $this->getCacheBaseUrl());
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getCacheBaseUrl() {
        return BASE_URL . '/images/cache';
    }
}
?>
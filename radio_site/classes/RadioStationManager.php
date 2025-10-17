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
                rs.tags,
                -- Добавляем кэшированные favicon'ы через LEFT JOIN
                CASE 
                    WHEN ic.status = 'success' THEN CONCAT(:cacheBaseUrl, '/', ic.local_path)
                    ELSE rs.favicon
                END as cached_favicon
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

    /**
     * Поиск SIM радиостанций по имени или тегу
     */
    public function searchSimStations($query, $type = 'name') {
        try {
            error_log("=== SIM Search Debug ===");
            error_log("Query: '{$query}', Type: '{$type}'");
            
            if ($type === 'name') {
                // Поиск по названию серии или станции - используем разные параметры
                $sql = "
                    SELECT 
                        srs.id as series_id,
                        srs.url,
                        srs.title as series_title,
                        srs.logo as series_logo,
                        srs_st.id as station_id,
                        srs_st.logo as station_logo,
                        srs_st.tags as station_tags,
                        srs_st.title as station_title
                    FROM sim_radio_series srs
                    INNER JOIN sim_radio_stations srs_st ON srs.id = srs_st.series_id
                    WHERE srs.title LIKE :query_series OR srs_st.title LIKE :query_station
                    ORDER BY srs.title, srs_st.title
                ";
            } else if ($type === 'tag') {
                // Поиск по тегам станций
                $sql = "
                    SELECT 
                        srs.id as series_id,
                        srs.url,
                        srs.title as series_title,
                        srs.logo as series_logo,
                        srs_st.id as station_id,
                        srs_st.logo as station_logo,
                        srs_st.tags as station_tags,
                        srs_st.title as station_title
                    FROM sim_radio_series srs
                    INNER JOIN sim_radio_stations srs_st ON srs.id = srs_st.series_id
                    WHERE srs_st.tags LIKE :query
                    ORDER BY srs.title, srs_st.title
                ";
            } else {
                error_log("Invalid search type: {$type}");
                return [];
            }
            
            error_log("SQL: {$sql}");
            
            $stmt = $this->db->prepare($sql);
            $searchPattern = '%' . $query . '%';
            
            if ($type === 'name') {
                $stmt->bindValue(':query_series', $searchPattern, PDO::PARAM_STR);
                $stmt->bindValue(':query_station', $searchPattern, PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':query', $searchPattern, PDO::PARAM_STR);
            }
            
            error_log("Executing with pattern: '{$searchPattern}'");
            
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Raw SQL results count: " . count($results));
            if (count($results) > 0) {
                error_log("First result: " . json_encode($results[0]));
            }
            
            // Группируем станции по сериям
            $grouped = $this->groupSimStationsBySeries($results);
            error_log("Grouped results: " . count($grouped) . " series");
            
            return $grouped;
            
        } catch(PDOException $e) {
            error_log("Database error in searchSimStations: " . $e->getMessage());
            error_log("Error code: " . $e->getCode());
            error_log("SQL: " . $sql ?? 'No SQL');
            return [];
        }
    }
    
    /**
     * Группирует найденные SIM станции по сериям
     */
    private function groupSimStationsBySeries($stations) {
        $grouped = [];
        
        foreach ($stations as $station) {
            $seriesId = $station['series_id'];
            
            if (!isset($grouped[$seriesId])) {
                $grouped[$seriesId] = [
                    'id' => $station['series_id'],
                    'url' => $station['url'],
                    'title' => $station['series_title'],
                    'logo' => $station['series_logo'],
                    'stations' => []
                ];
            }
            
            $grouped[$seriesId]['stations'][] = [
                'id' => $station['station_id'],
                'logo' => $station['station_logo'],
                'tags' => $station['station_tags'],
                'title' => $station['station_title']
            ];
        }
        
        return array_values($grouped);
    }

    public function getActiveStationByUUID($uuid) {
        $sql = "
            SELECT 
                rs.stationuuid, rs.name, rs.url, rs.favicon, 
                rs.votes, rs.clickcount, rs.clicktrend, rs.country, rs.language,
                rs.tags, rs.homepage,
                -- Добавляем кэшированные favicon'ы через LEFT JOIN
                CASE 
                    WHEN ic.status = 'success' THEN CONCAT(:cacheBaseUrl, '/', ic.local_path)
                    ELSE rs.favicon
                END as cached_favicon
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
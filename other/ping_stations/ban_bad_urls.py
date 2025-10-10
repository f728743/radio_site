import json
import mysql.connector
from datetime import datetime

def import_banned_stations(json_file_path):
    # Подключение к базе данных
    conn = mysql.connector.connect(
        host='localhost',
        database='cx10577_simradio',
        user='cx10577',
        password='f728743'
    )
    
    cursor = conn.cursor()
    
    try:
        # Чтение JSON файла
        with open(json_file_path, 'r', encoding='utf-8') as file:
            data = json.load(file)
        
        # Подготовка SQL запроса для вставки
        insert_query = """
        INSERT INTO banned_stations (stationuuid, reason_id, banned_at)
        VALUES (%s, %s, %s)
        """
        
        # Обработка каждой станции из JSON
        for station in data['bad_urls']:
            stationuuid = station['stationuuid']
            
            # Определение reason_id на основе reason
            if station['reason'] == 'SSL Error':
                reason_id = 1
            else:
                reason_id = 2
            
            # Текущее время для banned_at
            banned_at = datetime.now()
            
            # Выполнение вставки
            cursor.execute(insert_query, (stationuuid, reason_id, banned_at))
        
        # Подтверждение изменений
        conn.commit()
        print(f"Успешно добавлено {len(data['bad_urls'])} записей в таблицу banned_stations")
        
    except Exception as e:
        print(f"Ошибка: {e}")
        conn.rollback()
    
    finally:
        # Закрытие соединения
        cursor.close()
        conn.close()

# Использование функции
if __name__ == "__main__":
    json_file_path = "retest_bad_https_urls.json"  # Укажите путь к вашему JSON файлу
    import_banned_stations(json_file_path)
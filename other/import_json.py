import json
import mysql.connector
from datetime import datetime

# Чтение JSON файла
with open('stations.json', 'r', encoding='utf-8') as f:
    data = json.load(f)

# Подключение к MySQL
conn = mysql.connector.connect(
    host='localhost',
    user='cx10577',
    password='f728743',
    database='cx10577_simradio'
)
cursor = conn.cursor()

# Создание таблицы только с нужными полями
create_table_query = """
CREATE TABLE IF NOT EXISTS radio_stations (
    stationuuid VARCHAR(255) PRIMARY KEY,
    name VARCHAR(500),
    url TEXT,
    url_resolved TEXT,
    homepage TEXT,
    favicon TEXT,
    tags TEXT,
    country VARCHAR(100),
    countrycode VARCHAR(10),
    language VARCHAR(100),
    languagecodes VARCHAR(50),
    votes INT,
    lastchangetime DATETIME,
    codec VARCHAR(50),
    bitrate INT,
    hls BOOLEAN,
    lastcheckok BOOLEAN,
    lastchecktime DATETIME,
    lastcheckoktime DATETIME,
    lastlocalchecktime DATETIME,
    clicktimestamp DATETIME,
    clickcount INT,
    clicktrend INT,
    ssl_error INT,
    geo_lat FLOAT,
    geo_long FLOAT
)
"""
cursor.execute(create_table_query)

# Вставка данных - только нужные поля
insert_query = """
INSERT INTO radio_stations (
    stationuuid, name, url, url_resolved, homepage, favicon, 
    tags, country, countrycode, language, languagecodes, 
    votes, lastchangetime, codec, bitrate, hls, lastcheckok, 
    lastchecktime, lastcheckoktime, lastlocalchecktime, 
    clicktimestamp, clickcount, clicktrend, ssl_error, 
    geo_lat, geo_long
) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, 
          %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
"""

# Функция для преобразования дат
def parse_datetime(dt_str):
    if dt_str and dt_str != 'null':
        try:
            return datetime.strptime(dt_str, '%Y-%m-%d %H:%M:%S')
        except:
            try:
                return datetime.strptime(dt_str, '%Y-%m-%dT%H:%M:%SZ')
            except:
                return None
    return None

success_count = 0
error_count = 0

for station in data:
    try:
        values = (
            station.get('stationuuid'),
            station.get('name', '').strip() if station.get('name') else '',
            station.get('url'),
            station.get('url_resolved'),
            station.get('homepage'),
            station.get('favicon'),
            station.get('tags'),
            station.get('country'),
            station.get('countrycode'),
            station.get('language'),
            station.get('languagecodes'),
            station.get('votes', 0),
            parse_datetime(station.get('lastchangetime')),
            station.get('codec'),
            station.get('bitrate', 0),
            bool(station.get('hls', 0)),
            bool(station.get('lastcheckok', 0)),
            parse_datetime(station.get('lastchecktime')),
            parse_datetime(station.get('lastcheckoktime')),
            parse_datetime(station.get('lastlocalchecktime')),
            parse_datetime(station.get('clicktimestamp')),
            station.get('clickcount', 0),
            station.get('clicktrend', 0),
            station.get('ssl_error', 0),
            station.get('geo_lat'),
            station.get('geo_long')
        )
        
        cursor.execute(insert_query, values)
        success_count += 1
        
    except Exception as e:
        error_count += 1
        print(f"Ошибка при вставке станции {station.get('name')}: {e}")
        continue

conn.commit()
cursor.close()
conn.close()

print(f"Импорт завершен! Успешно: {success_count}, Ошибок: {error_count}")
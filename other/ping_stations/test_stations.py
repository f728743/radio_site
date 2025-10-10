import json
import requests
import concurrent.futures
from urllib.parse import urlparse
import time
import argparse

def check_stream(url, original_url):
    """
    Проверяет доступность стрима по HTTPS URL
    """
    test_url = url
    try:
        # Если URL начинается с http:, заменяем на https:
        if url.startswith('http:'):
            test_url = url.replace('http:', 'https:', 1)
        
        # Пропускаем URL, которые остались http:
        if test_url.startswith('http:'):
            return {
                'original_url': original_url,
                'tested_url': test_url,
                'status': 'skipped',
                'reason': 'HTTP protocol not supported'
            }
        
        headers = {
            'User-Agent': 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
            'Range': 'bytes=0-4096'
        }
        
        response = requests.get(
            test_url,
            headers=headers,
            timeout=15,
            stream=True
        )
        
        # Проверяем статус код
        if response.status_code in [200, 206]:
            # Пытаемся прочитать небольшой кусок данных
            data_chunk = next(response.iter_content(chunk_size=1024), None)
            if data_chunk and len(data_chunk) > 0:
                return {
                    'original_url': original_url,
                    'tested_url': test_url,
                    'status': 'good',
                    'http_status': response.status_code,
                    'content_type': response.headers.get('content-type', 'Unknown'),
                    'content_length': len(data_chunk)
                }
            else:
                return {
                    'original_url': original_url,
                    'tested_url': test_url,
                    'status': 'bad',
                    'reason': 'No data received',
                    'http_status': response.status_code
                }
        else:
            return {
                'original_url': original_url,
                'tested_url': test_url,
                'status': 'bad',
                'reason': f'HTTP status {response.status_code}',
                'http_status': response.status_code
            }
            
    except requests.exceptions.Timeout:
        return {
            'original_url': original_url,
            'tested_url': test_url,
            'status': 'bad',
            'reason': 'Timeout'
        }
    except requests.exceptions.SSLError:
        return {
            'original_url': original_url,
            'tested_url': test_url,
            'status': 'bad',
            'reason': 'SSL Error'
        }
    except requests.exceptions.ConnectionError:
        return {
            'original_url': original_url,
            'tested_url': test_url,
            'status': 'bad',
            'reason': 'Connection Error'
        }
    except requests.exceptions.RequestException as e:
        return {
            'original_url': original_url,
            'tested_url': test_url,
            'status': 'bad',
            'reason': f'Request Error: {str(e)}'
        }
    except Exception as e:
        return {
            'original_url': original_url,
            'tested_url': test_url,
            'status': 'bad',
            'reason': f'Unexpected error: {str(e)}'
        }

def format_time(seconds):
    """Форматирует время в читаемый вид"""
    if seconds < 60:
        return f"{seconds:.1f}сек"
    elif seconds < 3600:
        minutes = int(seconds // 60)
        secs = seconds % 60
        return f"{minutes}мин {secs:.0f}сек"
    else:
        hours = int(seconds // 3600)
        minutes = int((seconds % 3600) // 60)
        return f"{hours}ч {minutes}мин"

def run_check(stations, output_prefix=""):
    """Запускает проверку списка станций"""
    total_stations = len(stations)
    
    good_urls = []
    bad_urls = []
    
    start_time = time.time()
    
    # Проверяем URL параллельно
    with concurrent.futures.ThreadPoolExecutor(max_workers=100) as executor:
        # Создаем задачи для выполнения
        future_to_station = {
            executor.submit(check_stream, station['url'], station['url']): station 
            for station in stations
        }
        
        # Обрабатываем результаты по мере их поступления
        completed = 0
        for future in concurrent.futures.as_completed(future_to_station):
            station = future_to_station[future]
            try:
                result = future.result()
                
                if result['status'] == 'good':
                    good_urls.append({
                        'stationuuid': station['stationuuid'],
                        'original_url': result['original_url'],
                        'tested_url': result['tested_url'],
                        'http_status': result.get('http_status'),
                        'content_type': result.get('content_type'),
                        'content_length': result.get('content_length')
                    })
                    status_icon = '✅'
                elif result['status'] == 'skipped':
                    bad_urls.append({
                        'stationuuid': station['stationuuid'],
                        'original_url': result['original_url'],
                        'tested_url': result['tested_url'],
                        'reason': result.get('reason')
                    })
                    status_icon = '⏭️'
                else:
                    bad_urls.append({
                        'stationuuid': station['stationuuid'],
                        'original_url': result['original_url'],
                        'tested_url': result['tested_url'],
                        'reason': result.get('reason'),
                        'http_status': result.get('http_status')
                    })
                    status_icon = '❌'
                
                completed += 1
                
                # Расчет прогресса и времени
                elapsed_time = time.time() - start_time
                progress_percent = (completed / total_stations) * 100
                
                # Расчет оставшегося времени
                if completed > 0:
                    avg_time_per_task = elapsed_time / completed
                    remaining_tasks = total_stations - completed
                    estimated_remaining = avg_time_per_task * remaining_tasks
                    eta_str = format_time(estimated_remaining)
                else:
                    eta_str = "расчет..."
                
                elapsed_str = format_time(elapsed_time)
                
                print(f"{status_icon} [{completed}/{total_stations}] {progress_percent:.1f}% | "
                      f"Прошло: {elapsed_str} | Осталось: {eta_str} | "
                      f"{station['stationuuid'][:8]}... - {result.get('reason', 'OK')}")
                
            except Exception as e:
                completed += 1
                print(f"❌ Ошибка при обработке станции {station['stationuuid']}: {e}")
                bad_urls.append({
                    'stationuuid': station['stationuuid'],
                    'original_url': station['url'],
                    'tested_url': station['url'],
                    'reason': f'Processing error: {str(e)}'
                })
    
    total_time = time.time() - start_time
    
    return good_urls, bad_urls, total_time

def main():
    parser = argparse.ArgumentParser(description='Проверка HTTPS URL радиостанций')
    parser.add_argument('--retest', action='store_true', help='Режим повторной проверки проблемных URL')
    args = parser.parse_args()
    
    if args.retest:
        print("🔁 ЗАПУСК В РЕЖИМЕ RETEST")
        # Режим повторной проверки
        try:
            with open('bad_https_urls.json', 'r', encoding='utf-8') as f:
                data = json.load(f)
            
            stations = data.get('bad_urls', [])
            # Преобразуем структуру для совместимости с функцией проверки
            stations_for_check = []
            for station in stations:
                stations_for_check.append({
                    'stationuuid': station['stationuuid'],
                    'url': station['original_url']  # Используем оригинальный URL для повторной проверки
                })
            
            print(f"🔄 Перепроверяем {len(stations_for_check)} проблемных URL")
            
            good_urls, bad_urls, total_time = run_check(stations_for_check, "retest_")
            
            # Сохраняем результаты повторной проверки
            try:
                with open('retest_good_https_urls.json', 'w', encoding='utf-8') as f:
                    json.dump({'good_urls': good_urls}, f, indent=2, ensure_ascii=False)
                
                with open('retest_bad_https_urls.json', 'w', encoding='utf-8') as f:
                    json.dump({'bad_urls': bad_urls}, f, indent=2, ensure_ascii=False)
                
                print(f"\n" + "="*60)
                print(f"📊 РЕЗУЛЬТАТЫ ПОВТОРНОЙ ПРОВЕРКИ")
                print("="*60)
                print(f"✅ Восстановленные URL: {len(good_urls)}")
                print(f"❌ Все еще проблемные URL: {len(bad_urls)}")
                if len(stations_for_check) > 0:
                    recovery_rate = (len(good_urls) / len(stations_for_check)) * 100
                    print(f"📈 Восстановлено: {recovery_rate:.1f}%")
                print(f"⏱️  Общее время: {format_time(total_time)}")
                print(f"💾 Результаты сохранены в retest_good_https_urls.json и retest_bad_https_urls.json")
                
            except Exception as e:
                print(f"❌ Ошибка сохранения результатов: {e}")
            
        except FileNotFoundError:
            print("❌ Файл bad_https_urls.json не найден. Сначала запустите обычную проверку.")
            return
        except json.JSONDecodeError as e:
            print(f"❌ Ошибка чтения JSON: {e}")
            return
            
    else:
        # Обычный режим проверки
        print("🚀 ЗАПУСК В РЕЖИМЕ СТАНДАРТНОЙ ПРОВЕРКИ")
        try:
            with open('station_https_checks.json', 'r', encoding='utf-8') as f:
                data = json.load(f)
            
            # Извлекаем станции
            stations = data.get('station_https_checks', [])
            print(f"📻 Найдено {len(stations)} станций для проверки")
            
            good_urls, bad_urls, total_time = run_check(stations)
            
            # Сохраняем результаты
            try:
                with open('good_https_urls.json', 'w', encoding='utf-8') as f:
                    json.dump({'good_urls': good_urls}, f, indent=2, ensure_ascii=False)
                
                with open('bad_https_urls.json', 'w', encoding='utf-8') as f:
                    json.dump({'bad_urls': bad_urls}, f, indent=2, ensure_ascii=False)
                
                print(f"\n" + "="*60)
                print(f"📊 РЕЗУЛЬТАТЫ ПРОВЕРКИ")
                print("="*60)
                print(f"✅ Работающие URL: {len(good_urls)}")
                print(f"❌ Проблемные URL: {len(bad_urls)}")
                if len(stations) > 0:
                    print(f"📈 Успешность: {(len(good_urls)/len(stations))*100:.1f}%")
                print(f"⏱️  Общее время: {format_time(total_time)}")
                print(f"💾 Результаты сохранены в good_https_urls.json и bad_https_urls.json")
                print(f"💡 Для повторной проверки проблемных URL запустите: python script.py --retest")
                
            except Exception as e:
                print(f"❌ Ошибка сохранения результатов: {e}")
            
        except FileNotFoundError:
            print("❌ Файл station_https_checks.json не найден")
            return
        except json.JSONDecodeError as e:
            print(f"❌ Ошибка чтения JSON: {e}")
            return

if __name__ == "__main__":
    main()
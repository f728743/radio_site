function selectStation(radio) {
    const station = radio.closest('.station');
    station.classList.add('selected');
    
    const group = station.closest('.group');
    group.querySelectorAll('.station').forEach(s => {
        if (s !== station) {
            s.classList.remove('selected');
        }
    });
}

function banGroupDuplicates(button) {
    const group = button.closest('.group');
    const selectedRadio = group.querySelector('.station-radio:checked');
    const selectedUUID = selectedRadio.value;
    
    const allUUIDs = Array.from(group.querySelectorAll('.station'))
        .map(station => station.dataset.stationUuid)
        .filter(uuid => uuid !== selectedUUID);
    
    if (allUUIDs.length === 0) {
        alert('Нет станций для бана в этой группе');
        return;
    }
    
    if (!confirm(`Забанить ${allUUIDs.length} дубликатов?`)) {
        return;
    }
    
    banStations(allUUIDs, group);
}

function banAllSelected() {
    const allGroups = document.querySelectorAll('.group');
    let uuidsToBan = [];
    
    allGroups.forEach(group => {
        const selectedRadio = group.querySelector('.station-radio:checked');
        const selectedUUID = selectedRadio.value;
        
        const groupUUIDs = Array.from(group.querySelectorAll('.station'))
            .map(station => station.dataset.stationUuid)
            .filter(uuid => uuid !== selectedUUID);
        
        uuidsToBan = uuidsToBan.concat(groupUUIDs);
    });
    
    if (uuidsToBan.length === 0) {
        alert('Нет станций для бана');
        return;
    }
    
    if (!confirm(`Забанить ${uuidsToBan.length} дубликатов на всех страницах?`)) {
        return;
    }
    
    banStations(uuidsToBan);
}

function banStations(uuids, groupElement = null) {    
    const loading = document.getElementById('loading');
    loading.style.display = 'block';
    
    // Создаем FormData для правильной передачи массива
    const formData = new FormData();
    formData.append('action', 'ban_stations');
    formData.append('reason_id', 7);
    
    // Добавляем каждый UUID отдельно
    uuids.forEach(uuid => {
        formData.append('station_uuids[]', uuid);
    });
    
    // Получаем правильный URL для админки
    const adminUrl = document.querySelector('base')?.href || '';
    const ajaxUrl = `${adminUrl}ajax/ban_stations.php`;
    
    
    fetch(ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        loading.style.display = 'none';
        
        if (data.success) {
            alert(`Успешно забанено станций: ${data.banned_count}`);
            
            if (groupElement) {
                groupElement.remove();
            } else {
                location.reload();
            }
        } else {
            alert('Ошибка при бане станций: ' + (data.error || 'Неизвестная ошибка'));
        }
    })
    .catch(error => {
        loading.style.display = 'none';
        console.error('Fetch error:', error);
        
        // Более информативное сообщение об ошибке
        let errorMessage = 'Ошибка при выполнении запроса';
        if (error.message.includes('Failed to fetch')) {
            errorMessage += ' - проблема с сетью или URL';
        } else if (error.message.includes('HTTP error')) {
            errorMessage += ` - сервер вернул ошибку: ${error.message}`;
        }
        
        alert(errorMessage + '\n\nПроверьте консоль для деталей.');
    });
}
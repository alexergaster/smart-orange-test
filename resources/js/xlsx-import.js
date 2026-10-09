import * as XLSX from 'xlsx';

const BATCH_SIZE = 1000;
const MAX_RETRIES = 3;
const REQUEST_TIMEOUT = 25000;

const FIELDS = [
    'external_id',
    'created_at',
    'first_name',
    'last_name',
    'phone',
    'email',
    'city',
    'source',
    'utm_campaign',
    'product',
    'budget_uah',
    'status',
    'manager',
    'comment',
    'next_contact_at',
];

const fileInput = document.getElementById('xlsx-file');
const startButton = document.getElementById('start-import');
const statusElement = document.getElementById('status');
const progressElement = document.getElementById('progress');
const progressText = document.getElementById('progress-text');

const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    .content;

function setStatus(message, type = 'default') {
    statusElement.textContent = message;

    statusElement.classList.remove('success', 'error');

    if (type === 'success' || type === 'error') {
        statusElement.classList.add(type);
    }
}

function setProgress(processed, total) {
    const percentage = total
        ? Math.round(processed / total * 100)
        : 0;

    progressElement.value = percentage;
    progressText.textContent =
        `${percentage}% — ${processed} / ${total}`;
}

function textValue(value) {
    return String(value ?? '').trim();
}

function nullableText(value) {
    const result = textValue(value);
    return result === '' ? null : result;
}

function requiredText(value, field, rowNumber) {
    const result = textValue(value);

    if (!result) {
        throw new Error(
            `Рядок ${rowNumber}: поле ${field} порожнє`
        );
    }

    return result;
}

function numberValue(value, field, rowNumber) {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    if (typeof value === 'number') {
        if (Number.isFinite(value)) {
            return value;
        }
        throw new Error(`Рядок ${rowNumber}: invalid ${field}`);
    }

    const cleaned = textValue(value)
        .replace(/[\s\u00A0\u202F₴]/g, '')
        .replace(',', '.');

    if (!/^-?\d+(\.\d+)?$/.test(cleaned)) {
        throw new Error(
            `Рядок ${rowNumber}: некоректне число ${field}`
        );
    }

    const result = Number(cleaned);

    if (!Number.isFinite(result)) {
        throw new Error(
            `Рядок ${rowNumber}: некоректне число ${field}`
        );
    }

    return result;
}

const pad = (value) => String(value).padStart(2, '0');

function dateValue(value, field, rowNumber, date1904) {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    if (typeof value === 'number') {
        const date = XLSX.SSF.parse_date_code(value, {
            date1904,
        });

        if (!date || date.y < 1000) {
            throw new Error(
                `Рядок ${rowNumber}: неправильна дата ${field}`
            );
        }

        return (
            `${date.y}-${pad(date.m)}-${pad(date.d)} ` +
            `${pad(date.H)}:${pad(date.M)}:${pad(date.S)}`
        );
    }

    const str = textValue(value);

    if (/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}(:\d{2})?)?$/.test(str)) {
        const normalized = str.replace('T', ' ');

        if (normalized.length === 10) {
            return `${normalized} 00:00:00`;
        }

        if (normalized.length === 16) {
            return `${normalized}:00`;
        }

        return normalized;
    }

    const match = str.match(
        /^(\d{2})\.(\d{2})\.(\d{4})(?:\s+(\d{2}):(\d{2})(?::(\d{2}))?)?$/
    );

    if (match) {
        const [, d, m, y, h = '00', min = '00', s = '00'] = match;

        return `${y}-${m}-${d} ${h}:${min}:${s}`;
    }

    throw new Error(
        `Рядок ${rowNumber}: невідомий формат дати ${field}`
    );
}

function validateHeaders(sheet) {
    const headerRows = XLSX.utils.sheet_to_json(sheet, {
        header: 1,
        range: 'A1:O1',
        defval: null,
        blankrows: true,
        raw: true,
    });

    const headers = (headerRows[0] || []).map(value =>
        textValue(value).toLowerCase()
    );

    if (
        headers.length !== FIELDS.length ||
        new Set(headers).size !== FIELDS.length ||
        !FIELDS.every(field => headers.includes(field))
    ) {
        throw new Error(
            'Структура XLSX не відповідає очікуваним 15 колонкам. ' +
            `Очікуються: ${FIELDS.join(', ')}`
        );
    }

    return Object.fromEntries(
        FIELDS.map(field => [field, headers.indexOf(field)])
    );
}

function mapRow(cells, columns, rowNumber, date1904) {
    const get = (field) => cells[columns[field]];

    return {
        external_id: requiredText(
            get('external_id'), 'external_id', rowNumber
        ),

        created_at: dateValue(
            get('created_at'), 'created_at', rowNumber, date1904
        ),

        first_name: nullableText(get('first_name')),
        last_name: nullableText(get('last_name')),
        phone: nullableText(get('phone')),
        email: nullableText(get('email')),
        city: nullableText(get('city')),
        source: nullableText(get('source')),
        utm_campaign: nullableText(get('utm_campaign')),
        product: nullableText(get('product')),

        budget_uah: numberValue(
            get('budget_uah'), 'budget_uah', rowNumber
        ),

        status: nullableText(get('status')),
        manager: nullableText(get('manager')),
        comment: nullableText(get('comment')),

        next_contact_at: dateValue(
            get('next_contact_at'),
            'next_contact_at',
            rowNumber,
            date1904
        ),
    };
}

function getBatchRows(sheet, index, totalRows, columns, date1904) {
    const dataStart = index * BATCH_SIZE;
    const startRow = dataStart + 1;
    const endRow = Math.min(
        startRow + BATCH_SIZE - 1,
        totalRows
    );

    const range = XLSX.utils.encode_range({
        s: {r: startRow, c: 0},
        e: {r: endRow, c: FIELDS.length - 1},
    });

    const values = XLSX.utils.sheet_to_json(sheet, {
        header: 1,
        range,
        defval: null,
        blankrows: true,
        raw: true,
    });

    const expectedCount = endRow - startRow + 1;

    if (values.length !== expectedCount) {
        throw new Error(
            `Некоректна кількість рядків у пакеті ${index}`
        );
    }

    return values.map((cells, offset) =>
        mapRow(cells, columns, startRow + offset + 1, date1904)
    );
}

async function hashFile(buffer) {
    if (!window.crypto?.subtle) {
        throw new Error('Для імпорту необхідний HTTPS або localhost');
    }

    const hash = await crypto.subtle.digest('SHA-256', buffer);

    return Array.from(new Uint8Array(hash))
        .map(byte => byte.toString(16).padStart(2, '0'))
        .join('');
}


async function api(method, url, body = null) {
    const controller = new AbortController();

    const timeout = setTimeout(
        () => controller.abort(),
        REQUEST_TIMEOUT
    );

    try {
        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            signal: controller.signal,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: body === null
                ? undefined
                : JSON.stringify(body),
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const error = new Error(
                data.message || `HTTP ${response.status}`
            );

            error.status = response.status;
            error.retryable =
                response.status === 429 || response.status >= 500;

            throw error;
        }

        return data;
    } catch (error) {
        if (error.name === 'AbortError') {
            const timeoutError = new Error(
                'Перевищено час очікування HTTP-запиту'
            );

            timeoutError.retryable = true;
            throw timeoutError;
        }

        if (error instanceof TypeError) {
            error.retryable = true;
        }

        throw error;
    } finally {
        clearTimeout(timeout);
    }
}

async function sendBatch(importId, index, rows) {
    for (let attempt = 1; attempt <= MAX_RETRIES; attempt++) {
        try {
            return await api(
                'POST',
                `/imports/${importId}/batches`,
                {
                    batch_index: index,
                    rows,
                }
            );
        } catch (error) {
            if (!error.retryable || attempt === MAX_RETRIES) {
                throw error;
            }

            await new Promise(resolve =>
                setTimeout(resolve, attempt * 1000)
            );
        }
    }
}

async function importXlsx(file) {
    setProgress(0, 0);
    setStatus('Читання та аналіз XLSX...');

    let buffer = await file.arrayBuffer();
    const fileHash = await hashFile(buffer);

    const storageKey = `xlsx-import:${fileHash}`;

    const workbook = XLSX.read(buffer, {
        type: 'array',
        dense: true,
        cellDates: false,
    });

    buffer = null;

    const sheet = workbook.Sheets[workbook.SheetNames[0]];

    if (!sheet || !sheet['!ref']) {
        throw new Error('Перший аркуш XLSX порожній');
    }

    const usedRange = XLSX.utils.decode_range(sheet['!ref']);

    if (
        usedRange.s.r !== 0 ||
        usedRange.s.c !== 0 ||
        usedRange.e.c !== FIELDS.length - 1
    ) {
        throw new Error('Очікується таблиця A:O із заголовками в рядку 1');
    }

    const columns = validateHeaders(sheet);

    const totalRows = usedRange.e.r;
    const totalBatches = Math.ceil(totalRows / BATCH_SIZE);

    if (totalRows <= 0) {
        throw new Error('Немає рядків для імпорту');
    }

    const date1904 = Boolean(
        workbook.Workbook?.WBProps?.date1904
    );

    setStatus(
        `Знайдено ${totalRows} рядків. Пакетів: ${totalBatches}`
    );

    let importJob = null;
    const previousId = localStorage.getItem(storageKey);

    if (previousId) {
        try {
            const previous = await api(
                'GET',
                `/imports/${previousId}`
            );

            if (
                previous.status === 'processing' &&
                Number(previous.total_rows) === totalRows
            ) {
                importJob = previous;
            } else {
                localStorage.removeItem(storageKey);
            }
        } catch (error) {
            if (error.status !== 404) throw error;
            localStorage.removeItem(storageKey);
        }
    }

    if (!importJob) {
        importJob = await api('POST', '/imports', {
            total_rows: totalRows,
        });

        localStorage.setItem(storageKey, importJob.id);
    }

    const importId = importJob.id;

    const startBatch = Number(importJob.processed_batches);

    setProgress(
        Number(importJob.processed_rows),
        totalRows
    );

    for (let index = startBatch; index < totalBatches; index++) {
        setStatus(
            `Зберігаємо пакет ${index + 1} із ${totalBatches}...`
        );

        const rows = getBatchRows(
            sheet,
            index,
            totalRows,
            columns,
            date1904
        );

        const result = await sendBatch(importId, index, rows);

        setProgress(result.processed_rows, totalRows);
    }

    setStatus('Перевірка результату в MySQL...');

    const result = await api(
        'POST',
        `/imports/${importId}/complete`,
        {}
    );

    if (
        result.status !== 'completed' ||
        Number(result.imported) + Number(result.skipped) !== totalRows
    ) {
        throw new Error('Не всі рядки файлу були оброблені');
    }

    localStorage.removeItem(storageKey);

    setProgress(totalRows, totalRows);

    setStatus(
        `Імпорт завершено. Додано: ${result.imported}, ` +
        `пропущено: ${result.skipped}`,
        'success'
    );

    localStorage.removeItem(storageKey);

    setProgress(totalRows, totalRows);
    setStatus(
        `Успішно імпортовано ${result.inserted} рядків. Пропущено ${result.skipped}`,
        'success'
    );
}

startButton.addEventListener('click', async () => {
    const file = fileInput.files[0];

    if (!file) {
        setStatus('Виберіть XLSX-файл');
        return;
    }

    if (!file.name.toLowerCase().endsWith('.xlsx')) {
        setStatus('Підтримується тільки .xlsx');
        return;
    }

    startButton.disabled = true;
    fileInput.disabled = true;

    try {
        setStatus('Підготовка імпорту...');
        await new Promise(resolve => requestAnimationFrame(resolve));

        await importXlsx(file);
    } catch (error) {
        console.error(error);

        setStatus(
            `Помилка: ${error.message}. ` +
            'Повторно виберіть файл для відновлення імпорту.', 'error'
        );
    } finally {
        startButton.disabled = false;
        fileInput.disabled = false;
    }
});

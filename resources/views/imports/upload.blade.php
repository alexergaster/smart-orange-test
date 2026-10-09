
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Імпорт XLSX</title>

    @vite([
    'resources/css/xlsx-import.css',
    'resources/js/xlsx-import.js'
])
</head>
<body>
<main>
    <h2>Імпорт даних з Excel</h2>

    <input
        type="file"
        id="xlsx-file"
        accept=".xlsx"
    >

    <button type="button" id="start-import">
        Почати імпорт
    </button>

    <p id="status">Виберіть XLSX-файл</p>

    <progress
        id="progress"
        value="0"
        max="100"
        style="width: 400px; max-width: 100%;"
    ></progress>

    <p id="progress-text">0%</p>
</main>
</body>
</html>

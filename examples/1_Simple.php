<?php
// Пример базового использования с сохранением в файл.

require __DIR__ . '/../vendor/autoload.php';

use Igoramdtg\TextAndImage\TextImage;

// 1. Текст, который мы хотим нанести
$text = "Это базовый пример работы с библиотекой. Текст автоматически переносится на новые строки, " .
        "если он не помещается в заданную ширину. Вы можете легко менять цвет фона, текста и отступы.";

try {
    // 2. Создаем экземпляр класса
    $image = new TextImage($text);

    // 3. Настраиваем основные параметры
    $image->width = 800;
    $image->padding = 50;
    $image->background_color = '#2c3e50'; // Темно-синий фон
    $image->text_color = '#ecf0f1';       // Светлый текст
    $image->font = __DIR__ . '/assets/Vetrino.ttf'; // Убедитесь, что шрифт существует
    $image->text_size = 20;

    // 4. Сохраняем результат в файл
    if (!is_dir(__DIR__ . '/output')) mkdir(__DIR__ . '/output');
    $image->save(__DIR__ . '/output/01_basic_usage.png');

    echo "Изображение '01_basic_usage.png' успешно создано в папке /output.\n";

} catch (Exception $e) {
    echo 'Произошла ошибка: ' . $e->getMessage() . "\n";
}

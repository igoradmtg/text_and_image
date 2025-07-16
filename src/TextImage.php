<?php

/**
 * Библиотека для добавления текста на изображения с использованием PHP и GD.
 * Позволяет настраивать шрифт, цвет, выравнивание, фон и многое другое.
 *
 * @license MIT
 * @author  Igoramdtg
 */
namespace Igoramdtg\TextAndImage;

class TextImage {
    // --- Константы для информации о библиотеке ---
    const AUTHOR = 'Igoramdtg';
    const LIB_NAME = 'Text2Image';
    const VERSION = '1.5.0';
    const LICENSE = 'MIT';

    // --- Константы для выравнивания текста ---
    const ALIGN_LEFT = 'left';
    const ALIGN_CENTER = 'center';
    const ALIGN_RIGHT = 'right';
    const VALIGN_TOP = 'top';
    const VALIGN_MIDDLE = 'middle';
    const VALIGN_BOTTOM = 'bottom';

    /* # Общедоступные свойства, настраиваемые пользователем */

    /** @var int Ширина создаваемого изображения в пикселях. */
    public $width = 720;

    /** @var int|string Путь к файлу шрифта .ttf (в "умном" режиме) или индекс встроенного шрифта GD (1-5 в "простом" режиме). */
    public $font = __DIR__ . '/../examples/assets/Vetrino.ttf'; // Указан путь к примеру шрифта

    /** @var int|string Высота строки. 'auto' для автоматического расчета или целое число. */
    public $line_height = 'auto';

    /** @var array|string Цвет фона в формате [R, G, B, A] или HEX ('#RRGGBB' или '#RRGGBBAA'). */
    public $background_color = [20, 20, 20, 0];

    /** @var array|string Цвет текста в формате [R, G, B, A] или HEX. */
    public $text_color = [255, 255, 255, 0];

    /** @var int Внутренний отступ со всех сторон в пикселях. */
    public $padding = 30;

    /** @var int Угол наклона текста в градусах (только для "умного" режима). */
    public $angle = 0;

    /** @var int Размер шрифта в пунктах (только для "умного" режима). */
    public $text_size = 17;

    /** @var string Путь к файлу фонового изображения. Если указан, он будет использован вместо цвета фона. */
    public $background_image = '';

    /** @var string Горизонтальное выравнивание текста. Используйте константы: ALIGN_LEFT, ALIGN_CENTER, ALIGN_RIGHT. */
    public $align = self::ALIGN_LEFT;

    /** @var string Вертикальное выравнивание текста. Используйте константы: VALIGN_TOP, VALIGN_MIDDLE, VALIGN_BOTTOM. */
    public $valign = self::VALIGN_TOP;


    /* # Защищенные свойства для внутреннего использования */

    /** @var resource Ресурс изображения GD. */
    protected $image;

    /** @var bool Переключатель режима: true для "простого" (встроенные шрифты GD), false для "умного" (TTF шрифты). */
    protected $is_simple = false; // По умолчанию умный режим, т.к. он более функционален

    /** @var int Горизонтальный отступ для текста. */
    protected $offset_x = 0;

    /** @var int Вертикальный отступ для текста. */
    protected $offset_y = 0;

    /** @var int Реальная ширина области для текста (с учетом отступов). */
    protected $pseudo_width;

    /** @var int Рассчитанная высота изображения. */
    protected $height;

    /** @var int Количество символов в строке (только для "простого" режима). */
    protected $characters_per_line;

    /** @var string Исходный текст для нанесения на изображение. */
    protected $text;

    /** @var array Массив строк, готовых для отрисовки. */
    protected $lines;

    /**
     * Конструктор класса.
     *
     * @param string $text Исходный текст.
     */
    public function __construct($text = '') {
        if (!$this->is_supported()) {
            throw new \Exception('GD extension is not loaded. Please enable it in your php.ini file.');
        }
        $this->text = (string)$text;
    }

    /**
     * Устанавливает текст для изображения. Позволяет использовать цепочки методов.
     *
     * @param string $new_text Новый текст.
     * @return self
     */
    public function setText($new_text = '') {
        $this->text = $new_text;
        return $this;
    }
    
    /**
     * Устанавливает цвет текста.
     *
     * @param string|array $color Цвет в формате HEX ('#FFFFFF') или RGBA ([255, 255, 255, 0]).
     * @return self
     */
    public function setTextColor($color) {
        $this->text_color = $color;
        return $this;
    }

    /**
     * Устанавливает цвет фона.
     *
     * @param string|array $color Цвет в формате HEX ('#000000') или RGBA ([0, 0, 0, 0]).
     * @return self
     */
    public function setBackgroundColor($color) {
        $this->background_color = $color;
        return $this;
    }
    
    /**
     * Устанавливает путь к файлу шрифта.
     *
     * @param string $font_path Путь к .ttf файлу.
     * @return self
     */
    public function setFont($font_path) {
        $this->font = $font_path;
        $this->is_simple = false; // Использование TTF шрифта автоматически включает "умный" режим
        return $this;
    }

    /**
     * Устанавливает размер шрифта.
     *
     * @param int $size Размер шрифта.
     * @return self
     */
    public function setFontSize($size) {
        $this->text_size = (int)$size;
        return $this;
    }
    
    /**
     * Устанавливает выравнивание текста.
     *
     * @param string $align Горизонтальное выравнивание (left, center, right).
     * @param string $valign Вертикальное выравнивание (top, middle, bottom).
     * @return self
     */
    public function setAlignment($align = self::ALIGN_LEFT, $valign = self::VALIGN_TOP) {
        $this->align = $align;
        $this->valign = $valign;
        return $this;
    }
    
    /**
     * Применяет фильтр "оттенки серого" к изображению.
     *
     * @return self
     */
    public function applyGrayscale() {
        if ($this->image) {
            imagefilter($this->image, IMG_FILTER_GRAYSCALE);
        }
        return $this;
    }
    
    /**
     * Изменяет яркость изображения.
     *
     * @param int $level Уровень яркости. От -255 (темный) до 255 (светлый).
     * @return self
     */
    public function applyBrightness($level) {
        if ($this->image) {
            imagefilter($this->image, IMG_FILTER_BRIGHTNESS, $level);
        }
        return $this;
    }

    /**
     * Сохраняет итоговое изображение в файл.
     *
     * @param string $path Путь для сохранения файла.
     * @param string $type Формат изображения ('png', 'jpg', 'gif').
     * @param int $quality Качество для jpg/png (0-100 для jpg, 0-9 для png).
     * @return void
     */
    public function save($path, $type = 'png', $quality = 90) {
        $this->render();
        switch (strtolower($type)) {
            case 'gif':
                imagegif($this->image, $path);
                break;
            case 'jpg':
            case 'jpeg':
                imagejpeg($this->image, $path, ($quality > 100) ? 100 : $quality);
                break;
            case 'wbmp':
                imagewbmp($this->image, $path);
                break;
            case 'png':
            default:
                // Качество для PNG - это уровень сжатия (0-9). Конвертируем 0-100 в 9-0.
                $png_quality = 9 - round(($quality / 100) * 9);
                imagepng($this->image, $path, $png_quality);
                break;
        }
        imagedestroy($this->image);
    }

    /**
     * Выводит итоговое изображение напрямую в браузер.
     *
     * @param string $type Формат изображения ('png', 'jpg', 'gif').
     * @param int $quality Качество для jpg/png.
     * @return void
     */
    public function output($type = 'png', $quality = 90) {
        $this->render();
        header("Content-type: image/" . strtolower($type));
        // Этот блок идентичен блоку в save(), но выводит в null (браузер)
        switch (strtolower($type)) {
            case 'gif':
                imagegif($this->image);
                break;
            case 'jpg':
            case 'jpeg':
                imagejpeg($this->image, null, ($quality > 100) ? 100 : $quality);
                break;
            case 'wbmp':
                imagewbmp($this->image);
                break;
            case 'png':
            default:
                $png_quality = 9 - round(($quality / 100) * 9);
                imagepng($this->image, null, $png_quality);
                break;
        }
        imagedestroy($this->image);
    }

    /**
     * Основной метод, который генерирует изображение.
     * Вызывается автоматически методами save() или output().
     */
    protected function render() {
        $this->parse_text();

        // Рассчитываем итоговую высоту изображения
        $this->height = count($this->lines) * $this->line_height;
        if ($this->padding) {
            $this->height += $this->padding * 2;
        }

        // Создаем холст
        $this->image = imagecreatetruecolor($this->width, $this->height);

        // Включаем прозрачность
        imagesavealpha($this->image, true);
        imagealphablending($this->image, true);
        
        // Конвертируем HEX цвета в RGBA, если необходимо
        $bg_color_rgba = is_array($this->background_color) ? $this->background_color : $this->hex2rgb($this->background_color);
        $text_color_rgba = is_array($this->text_color) ? $this->text_color : $this->hex2rgb($this->text_color);

        // --- 1. Рисуем фон (изображение или цвет) ---
        if (!empty($this->background_image) && file_exists($this->background_image)) {
            $this->draw_background_image();
        } else {
            $bg_color_res = imagecolorallocatealpha($this->image, $bg_color_rgba[0], $bg_color_rgba[1], $bg_color_rgba[2], $bg_color_rgba[3]);
            imagefill($this->image, 0, 0, $bg_color_res);
        }

        // --- 2. Рисуем текст ---
        $text_color_res = imagecolorallocatealpha($this->image, $text_color_rgba[0], $text_color_rgba[1], $text_color_rgba[2], $text_color_rgba[3]);
        
        // Вертикальное выравнивание
        $total_text_height = count($this->lines) * $this->line_height;
        $y_start = $this->offset_y; // Начало для valign 'top'

        if ($this->valign === self::VALIGN_MIDDLE) {
            $y_start = ($this->height - $total_text_height) / 2 + $this->text_size;
        } elseif ($this->valign === self::VALIGN_BOTTOM) {
            $y_start = $this->height - $total_text_height - $this->padding + $this->text_size;
        }
        
        $current_y = $y_start;

        foreach ($this->lines as $line) {
            $line = trim($line);
            $x_start = $this->offset_x; // Начало для align 'left'

            // Горизонтальное выравнивание
            if ($this->align !== self::ALIGN_LEFT) {
                $line_width = $this->calculate_text_width($line);
                if ($this->align === self::ALIGN_CENTER) {
                    $x_start = ($this->width - $line_width) / 2;
                } elseif ($this->align === self::ALIGN_RIGHT) {
                    $x_start = $this->width - $line_width - $this->padding;
                }
            }
            
            if ($this->is_simple) {
                imagestring($this->image, $this->font, $x_start, $current_y - $this->text_size, $line, $text_color_res);
            } else {
                imagettftext($this->image, $this->text_size, $this->angle, $x_start, $current_y, $text_color_res, $this->font, $line);
            }
            $current_y += $this->line_height;
        }
    }

    /**
     * Рисует фоновое изображение на холсте.
     */
    protected function draw_background_image() {
        $bg_info = getimagesize($this->background_image);
        $bg_mime = $bg_info['mime'];

        switch ($bg_mime) {
            case 'image/jpeg': $bg_res = imagecreatefromjpeg($this->background_image); break;
            case 'image/png': $bg_res = imagecreatefrompng($this->background_image); break;
            case 'image/gif': $bg_res = imagecreatefromgif($this->background_image); break;
            default: return; // Неподдерживаемый тип
        }
        
        // Масштабируем фоновое изображение, чтобы оно заполнило холст
        imagecopyresampled(
            $this->image, $bg_res,
            0, 0, 0, 0,
            $this->width, $this->height,
            imagesx($bg_res), imagesy($bg_res)
        );
        
        imagedestroy($bg_res);
    }
    
    /**
     * Подготавливает переменные, необходимые для расчетов.
     */
    protected function make_definitions() {
        $this->pseudo_width = $this->width - ($this->padding * 2);

        // Устанавливаем базовые отступы
        $this->offset_x = $this->padding;
        $this->offset_y = $this->padding + ($this->is_simple ? 0 : $this->text_size);

        // Автоматический расчет высоты строки
        if ($this->line_height == 'auto') {
            if ($this->is_simple) {
                $this->line_height = imagefontheight($this->font) * 1.5;
            } else {
                $this->line_height = $this->text_size * 1.5;
            }
        }

        if ($this->is_simple) {
            $this->characters_per_line = floor($this->pseudo_width / imagefontwidth($this->font));
        }
    }

    /**
     * Разбивает исходный текст на строки, которые помещаются в заданную ширину.
     */
    protected function parse_text() {
        $this->make_definitions();
        $this->lines = [];
        
        if (empty($this->text)) {
            return;
        }
        
        // Разбиваем текст на строки по символу новой строки
        $source_lines = preg_split("#(?:\r)?\n#", $this->text);

        foreach ($source_lines as $line) {
            // Если строка помещается в область, просто добавляем ее
            if ($this->calculate_text_width($line) <= $this->pseudo_width) {
                $this->lines[] = $line;
                continue;
            }
            
            // Если нет, разбиваем строку по словам
            $words = preg_split('#(\s+)#', $line, -1, PREG_SPLIT_DELIM_CAPTURE);
            $current_line = '';

            foreach ($words as $word) {
                // Проверяем, поместится ли следующее слово в текущую строку
                $temp_line = $current_line . $word;
                if ($this->calculate_text_width($temp_line) > $this->pseudo_width) {
                    // Если нет, сохраняем текущую строку и начинаем новую со слова
                    $this->lines[] = trim($current_line);
                    $current_line = $word;
                } else {
                    // Если да, добавляем слово к текущей строке
                    $current_line = $temp_line;
                }
            }
            // Добавляем последнюю собранную строку
            $this->lines[] = trim($current_line);
        }
    }

    /**
     * Рассчитывает ширину строки текста в пикселях.
     *
     * @param string $text Строка для измерения.
     * @return int Ширина в пикселях.
     */
    protected function calculate_text_width($text) {
        if ($this->is_simple) {
            return mb_strlen($text, 'utf-8') * imagefontwidth($this->font);
        } else {
            if (!file_exists($this->font)) {
                // Если шрифт не найден, возвращаем 0, чтобы избежать ошибок
                return 0;
            }
            $box = imagettfbbox($this->text_size, $this->angle, $this->font, $text);
            return abs($box[4] - $box[0]);
        }
    }

    /**
     * Конвертирует цвет из формата HEX в массив RGBA.
     * Поддерживает #rgb, #rrggbb, #rrggbbaa.
     *
     * @param string $hex HEX-код цвета.
     * @return array Массив [R, G, B, A]. Альфа-канал: 0 (непрозрачный) - 127 (прозрачный).
     */
    protected function hex2rgb($hex) {
        $hex = str_replace('#', '', $hex);
        $length = strlen($hex);

        switch($length) {
            case 3: // #rgb
                $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
                $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
                $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
                $a = 0; // непрозрачный
                break;
            case 6: // #rrggbb
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                $a = 0; // непрозрачный
                break;
            case 8: // #rrggbbaa
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                // Альфа в HEX: 00 (прозрачный) - FF (непрозрачный)
                // Альфа в GD: 127 (прозрачный) - 0 (непрозрачный)
                $a_hex = hexdec(substr($hex, 6, 2));
                $a = intval(127 - ($a_hex / 255) * 127);
                break;
            default:
                return [0, 0, 0, 0]; // Возвращаем черный цвет по умолчанию
        }
        return [$r, $g, $b, $a];
    }

    /**
     * Проверяет, загружено ли расширение GD.
     *
     * @return bool
     */
    public function is_supported() {
        return extension_loaded('gd');
    }
}

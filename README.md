# text_and_image

# Install

Install php , php-gd, php-mbstring.

Download all files.

# Usage

require __DIR__ . "/textimage.class.php";

$test = new Igoramdtg\Text2Image\TextImage('Hello world!');

//$test->output();

$fname_save = '1_Simple.png';

echo "Save $fname_save \r\n";

$test->save($fname_save,'png');

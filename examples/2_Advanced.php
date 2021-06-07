<?php

require __DIR__ . "/../textimage.class.php";

$test = new Igoramdtg\TextAndImage\TextImage(
"
Lorem Ipsum is simply dummy text of the printing and typesetting industry.
Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged.
Привет Мир
"
);

$ar_fonts = array (
  'MyFont1',
  'MyFont2',
  'MyFont3',
  'MyFont4',
  'MyFont5',
);
$test->set_mode('smart');
$test->add_font($ar_fonts[0], __DIR__ . '/assets/foughtknight.ttf');
$test->add_font($ar_fonts[1], __DIR__ . '/assets/shrvan.ttf');
$test->add_font($ar_fonts[2], __DIR__ . '/assets/shrcass.ttf');
$test->add_font($ar_fonts[3], __DIR__ . '/assets/shrcomp.ttf');
$test->add_font($ar_fonts[4], __DIR__ . '/assets/shraglett.otf');
$test->set_background_image(__DIR__ . '/img/example1.jpg');
// also, smart mode supports text-size property and angle property (last one shown in 5th example)
$test->text_size = 30;
$test->width = 720; // custom width
$test->background_color = '#4C00993F'; // custom background color
$test->text_color = '#FF53FF00'; // custom text color
$test->line_height = 40; // custom line height
$test->padding = 50; // custom padding
$test->angle = 5;//negative values also supported

#$test->output();
foreach($ar_fonts as $key=>$font_name) {
  $test->font = $test->get_font($font_name);
  $fname_save = '2_Advanced_'.$key.'.jpg';
  echo "Save $fname_save \r\n";
  $test->save($fname_save,'jpg',95);
}
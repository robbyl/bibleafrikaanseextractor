
<form action="" method="post">
    <p>Import books <button name="books" value="books">Upload</button></p>
    <p>Import verses <button name="verses" value="verses">Upload</button></p>
    <p>Import selected reading verse categories <button name="category" value="category">Upload</button></p>
    <p>Import selected reading verses <button name="special_verses" value="special">Upload</button></p>

</form>

<?php
error_reporting(E_ALL & ~E_NOTICE);
include './simple_html_dom.php';

$db = new MyDB();

if (!empty($_POST['books'])) {

    $db->exec("DROP TABLE IF EXISTS key_english");
    $sql = 'CREATE TABLE "key_english" (
  "b" integer PRIMARY KEY AUTOINCREMENT  NOT NULL,
  "n" text NOT NULL
)';

    $verseQ = "";
    $db->exec($sql);

    echo "Importing books <br/>";

    $html = file_get_html("C:/xampp/htdocs/biblesw/index.htm");

    foreach ($html->find('ul') as $ul) {
        foreach ($ul->find('a') as $li) {
            $wacha = str_get_html($li);
            $trimed = trim($wacha->plaintext);
            $verseQ = $verseQ . "('{$trimed}'),";
        }
    }

    $build_books = "INSERT INTO key_english (n) VALUES " . substr(trim($verseQ), 0, -1);

//    echo $build_books;

    $db->exec($build_books);

    echo "<br /> Import books finished";
}

if (!empty($_POST['verses'])) {

    $db->exec("DROP TABLE IF EXISTS t_sw");
    $sql = 'CREATE TABLE "t_sw" (
  "id" integer zerofill PRIMARY KEY NOT NULL,
  "b" integer NOT NULL,
  "c" integer NOT NULL,
  "v" integer NOT NULL,
  "t" text NOT NULL
)';

    $db->exec($sql);

    echo "Import verses to SQLite db <br/>";

    $path = realpath('C:/xampp/htdocs/biblesw/sw');
    $iterator = new RecursiveDirectoryIterator($path);
    $iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
    $objects = new RecursiveIteratorIterator($iterator);

    $part = "";

    foreach ($objects as $name) {

//    echo $name . "<br/>";

        $exploded = explode('\\', $name);

        $book = $exploded[5];
        $chapter_explode = explode(".", $exploded[6]);
        $chapter = $chapter_explode[0];

        $html = file_get_html($name);

        // remove unwanted html tags
        foreach ($html->find('span,h3,a,.textAudio,.textHeader,.ym-wbox,.alignCenter,.ym-noprint') as $removed) {
            $removed->outertext = '';
        }

        foreach ($html->find('p', 1) as $element) {

            $some = str_get_html($element->innertext);

            if (!empty($some)) {
                $final = explode(PHP_EOL, trim($some->plaintext));

                for ($index = 0; $index < count($final); $index++) {
//                echo ($index + 1) . " " . $final[$index] . "<br/>";
                    $key = $index + 1;
                    $book1 = str_pad($book, 2, "0", STR_PAD_LEFT);
                    $chapter1 = str_pad($chapter, 3, "0", STR_PAD_LEFT);
                    $key1 = str_pad($key, 3, "0", STR_PAD_LEFT);

                    $trimed_verse = trim($final[$index]);
                    $part = $part . "({$book1}{$chapter1}{$key1},{$book}, {$chapter}, {$key}, \"{$trimed_verse}\"),";
                }
            }
        }
//    exit;
    }

    $build_query = "INSERT INTO t_sw (id, b, c, v, t) VALUES " . substr(trim($part), 0, -1);
    $db->exec($build_query);


    echo "Importing verses finished";
}

if (!empty($_POST['category'])) {
    echo 'Uploading special verses category';
    echo '<br/>';

    $db->exec("DROP TABLE IF EXISTS special_verses_category");

    $sql = 'CREATE TABLE "special_verses_category" (
         "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
         "category" TEXT
         )';

    $db->exec($sql);

    $html = file_get_html("C:/xampp/htdocs/biblesw/bibles/verses/swahili/index.htm");

    $categoryText = "";

    foreach ($html->find('ul.nav-tabs a') as $category) {
        $category = str_get_html($category->innertext); //removing a tag
        $category = end(explode('] ', $category));
        $categoryText = $categoryText . "('{$category}'),";
    }

    $build_categories = "INSERT INTO special_verses_category (category) VALUES " . substr(trim($categoryText), 0, -1);
//
//    echo $build_categories;
//    exit;

    $db->exec($build_categories);

    echo "Finished loading categories";
}

if (!empty($_POST['special_verses'])) {
    echo 'Uploading special verses <br/>';

    $db->exec("DROP TABLE IF EXISTS special_verses");
    $sql = 'CREATE TABLE "special_verses" (
         "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
         "verse_id" INTEGER ZEROFILL NOT NULL,
         "verse_category_id" INTEGER NOT NULL,
         "verse_no" TEXT NOT NULL,
         "verse_text" TEXT NOT NULL
         )';

    $db->exec($sql);

    $path = realpath('C:/xampp/htdocs/biblesw/bibles/verses/swahili');
    $iterator = new RecursiveDirectoryIterator($path);
    $iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
    $objects = new RecursiveIteratorIterator($iterator);

    $verse_category = 1;
    $verseText = "";

    foreach ($objects as $name) {

        $html = file_get_html($name);
        $trimed = "";

        foreach ($html->find('ul.shadetabs li') as $element) {
            $some = str_get_html($element->innertext); //removing li tag
            $verse_no = $element->find('a', 0)->innertext;

            $explode = explode(":", $verse_no);
            $first_explode = $explode[0];
            $last_explode = end($explode);
            $explode_first = explode(" ", $first_explode);
            $chapter_no = array_pop($explode_first);
            $slitted = multiexplode(array(',', '-'), $last_explode);

            $verse_no2 = $slitted[0];
            $book_name = implode(" ", $explode_first);
            $book_name = trim($book_name);

            $result = $db->query("SELECT b FROM key_english WHERE n = '{$book_name}' LIMIT 1");
            $result_array = $result->fetchArray();
            $book_no = $result_array[0];

            $book_no = str_pad($book_no, 2, "0", STR_PAD_LEFT);
            $chapter_no = str_pad($chapter_no, 3, "0", STR_PAD_LEFT);
            $verse_no2 = str_pad($verse_no2, 3, "0", STR_PAD_LEFT);
            $verse_id = "{$book_no}{$chapter_no}{$verse_no2}";

            $verses = explode('<', $some);
            $trimed = SQLite3::escapeString(trim($verses[0]));

            if (!empty($trimed)) {
                $verseText = $verseText . '(' . $verse_category . ',"' . $verse_id . '","' . $verse_no . '","' . $trimed . '"),';
            }
        }

        if (!empty($trimed)) {
            $verse_category++;
        }
    }

    $build_special_verses = "INSERT INTO special_verses (verse_category_id, verse_id, verse_no, verse_text)"
            . " VALUES " . substr(trim($verseText), 0, -1);
//    echo $build_special_verses;
//    exit;
    $db->exec($build_special_verses);

    echo "Finished importing special verses";
}

class MyDB extends SQLite3 {

    function __construct() {
        $this->open('bible_sw.db');
    }

}

function multiexplode($delimiters, $string) {

    $ready = str_replace($delimiters, $delimiters[0], $string);
    $launch = explode($delimiters[0], $ready);
    return $launch;
}
?>

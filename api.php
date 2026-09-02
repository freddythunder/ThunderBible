<?php
session_start();
require_once('class.Bible.php');

class BibleApi
{

    private $Bible;

    public function __construct()
    {
        $this->Bible = new Bible();
        if (method_exists($this, $_REQUEST['cmd'])) {
            return $this->{$_REQUEST['cmd']}();
        }
    }

    private function getBibles() {
        if (($_SESSION['bibles'] ?? null) && $_SESSION['bibles'] !== '') {
            $bibles = $_SESSION['bibles'];
        } else {
            $bibles = $this->Bible->getBibles();
            $_SESSION['bibles'] = $bibles;
        }
        echo $bibles;
    }

    private function getBooks() {
        $books = $this->Bible->getBooks($_REQUEST['bibleId'] ?? null);
        echo $books;
    }

    private function getChapters() {
        $chapters = $this->Bible->getChapters($_REQUEST['bibleId'] ?? null, $_REQUEST['bookId'] ?? null);
        echo $chapters;
    }

    private function getVerses() {
        $verses = $this->Bible->getVerses($_REQUEST['bibleId'] ?? null, $_REQUEST['chapterId'] ?? null);
        echo $verses;
    }

    private function getBibleHub() {
        /** TIL that "lexicon" is correct for the name of what I want
         * however what I didn't remember is biblehub offers once verse at a time
         * and they have Strong's numbers for a word or few as a token that
         * leads to the original text GPT told me to use this:
         *  https://biblesdk.com/api/books/GEN/chapters/1/verses/5?concordance=true
         *  but that was 404 today */
        $lexicon = $this->Bible->getBibleHub($_REQUEST['book'] ?? null, $_REQUEST['chapter'] ?? null, $_REQUEST['verse'] ?? null);
        echo $lexicon;
    }

}

new BibleApi();
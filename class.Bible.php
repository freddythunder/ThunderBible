<?php

class Bible 
{
	
	private $apiKey;

    private $bibleURL;

	public function __construct() {
        $this->apiKey = $_SERVER['BIBLEAPI'];
        $this->bibleURL = "https://api.scripture.api.bible/v1";

	}

    public function getBibles() {
        $url = '/bibles';
        return $this->callAPI($url);
    }

    public function getBooks($bibleId) {
        // get all books
        $url = '/bibles/' . $bibleId .'/books';
        return $this->callAPI($url);
    }

    public function getChapters($bibleId, $bookId) {
        $url = '/bibles/' . $bibleId . '/books/' . $bookId . '/chapters';
        return $this->callAPI($url);
    }

    public function getVerses($bibleId, $chapterId) {
        $url = '/bibles/' . $bibleId . '/chapters/' . $chapterId;
        return $this->callAPI($url);
    }

    private function callAPI($url) {
        // Build the stream context for GET + headers
        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => "api-key: {$this->apiKey}\r\n" .
                    "Accept: application/json\r\n",
                'timeout' => 10,
                'ignore_errors' => true // allow body even on non-200
            ]
        ]);

        // Call the upstream API
        $json = file_get_contents($this->bibleURL . $url, false, $context);

        if ($json === false) {
            return json_encode([
                'error' => 'Failed to connect',
                'response' => $json
            ]);
        }
        return $json;
    }
	
	public function getBibleHub($book, $chapter, $verse) {
		$book = str_replace(" ", "_", $book);
		$url = "https://biblehub.com/text/$book/$chapter-$verse.htm";

		$response = file_get_contents($url);

		preg_match("/<table width=\"100%\" border=\"0\" cellspacing=\"1\" cellpadding=\"5\" class=\"maintext\">(.*?)<\/table>/si", $response, $matches);

		return $matches[0];
	}

}
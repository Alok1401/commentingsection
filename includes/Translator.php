<?php
/**
 * Translator Class
 * Uses MyMemory free translation API
 * Caches translations in database to reduce API calls
 */

class Translator {
    private $conn;
    private $apiUrl = 'https://api.mymemory.translated.net/get';

    // Supported languages
    public static $languages = [
        'en' => 'English',
        'hi' => 'Hindi',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
        'ar' => 'Arabic',
        'zh' => 'Chinese',
        'ja' => 'Japanese',
        'ko' => 'Korean',
        'pt' => 'Portuguese',
        'ru' => 'Russian',
        'it' => 'Italian',
        'tr' => 'Turkish',
        'nl' => 'Dutch',
        'sv' => 'Swedish',
        'pl' => 'Polish',
        'th' => 'Thai',
        'vi' => 'Vietnamese',
        'id' => 'Indonesian',
        'ms' => 'Malay'
    ];

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Translate text from source to target language
     * Checks cache first, then calls API
     */
    public function translate($commentId, $text, $sourceLang, $targetLang) {
        if ($sourceLang === $targetLang) {
            return ['translated' => $text, 'cached' => true];
        }

        // Check cache
        $cached = $this->getFromCache($commentId, $sourceLang, $targetLang);
        if ($cached !== null) {
            return ['translated' => $cached, 'cached' => true];
        }

        // Call API
        $langPair = $sourceLang . '|' . $targetLang;
        $url = $this->apiUrl . '?' . http_build_query([
            'q' => $text,
            'langpair' => $langPair
        ]);

        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET',
                'header' => 'User-Agent: CommentSystem/1.0'
            ]
        ]);

        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return ['error' => 'Translation service unavailable. Please try again.'];
        }

        $data = json_decode($response, true);

        if (isset($data['responseData']['translatedText'])) {
            $translatedText = $data['responseData']['translatedText'];
            
            // Cache the translation
            $this->saveToCache($commentId, $sourceLang, $targetLang, $translatedText);
            
            return ['translated' => $translatedText, 'cached' => false];
        }

        return ['error' => 'Translation failed. Please try again.'];
    }

    /**
     * Get cached translation
     */
    private function getFromCache($commentId, $sourceLang, $targetLang) {
        $stmt = mysqli_prepare($this->conn, 
            "SELECT translated_text FROM translations_cache WHERE comment_id = ? AND source_lang = ? AND target_lang = ?"
        );
        mysqli_stmt_bind_param($stmt, "iss", $commentId, $sourceLang, $targetLang);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return $row ? $row['translated_text'] : null;
    }

    /**
     * Save translation to cache
     */
    private function saveToCache($commentId, $sourceLang, $targetLang, $translatedText) {
        $stmt = mysqli_prepare($this->conn,
            "INSERT INTO translations_cache (comment_id, source_lang, target_lang, translated_text) 
             VALUES (?, ?, ?, ?) 
             ON DUPLICATE KEY UPDATE translated_text = VALUES(translated_text)"
        );
        mysqli_stmt_bind_param($stmt, "isss", $commentId, $sourceLang, $targetLang, $translatedText);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    /**
     * Get supported languages list
     */
    public static function getSupportedLanguages() {
        return self::$languages;
    }
}
?>

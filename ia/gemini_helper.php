<?php
require_once __DIR__ . '/config.php';

/**
 * Appelle l'API Gemini et retourne le texte généré
 */
function callGemini($prompt, $temperature = 0.7) {
    $url = GEMINI_API_URL . GEMINI_MODEL . ':generateContent?key=' . GEMINI_API_KEY;

    $data = [
        'contents' => [[
            'parts' => [['text' => $prompt]]
        ]],
        'generationConfig' => [
            'temperature' => $temperature,
            'maxOutputTokens' => 2048,
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['success' => false, 'error' => "HTTP $httpCode", 'text' => null];
    }

    $result = json_decode($response, true);
    $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
    
    return ['success' => (bool)$text, 'text' => $text, 'raw' => $result];
}

/**
 * Extrait le JSON d'une réponse Gemini (même si entouré de markdown)
 */
function extractJsonFromGemini($text) {
    if (!$text) return null;
    // Supprimer les blocs markdown ```json ... ```
    $text = preg_replace('/```json\s*/', '', $text);
    $text = preg_replace('/```\s*/', '', $text);
    $text = trim($text);
    $decoded = json_decode($text, true);
    return $decoded;
}
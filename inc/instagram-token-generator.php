<?php
/**
 * Instagram Token Generator - POUZE PRO VYGENEROVÁNÍ TOKENU
 * Po získání tokenu tento soubor SMAŽTE!
 */

// Vaše údaje z Facebook Developer Console
define('INSTAGRAM_APP_ID', 'VAŠE_APP_ID');
define('INSTAGRAM_APP_SECRET', 'VAŠE_APP_SECRET');
define('REDIRECT_URI', 'https://preview.titbit.cz/instagram-callback/');

// Krok 1: Získání autorizačního kódu
if (!isset($_GET['code']) && !isset($_GET['access_token'])) {
    $auth_url = "https://api.instagram.com/oauth/authorize?" . http_build_query([
        'client_id' => INSTAGRAM_APP_ID,
        'redirect_uri' => REDIRECT_URI,
        'scope' => 'user_profile,user_media',
        'response_type' => 'code'
    ]);
    
    echo '<h2>Instagram Token Generator</h2>';
    echo '<p><a href="' . $auth_url . '">1. Klikněte zde pro autorizaci</a></p>';
    echo '<p>Po autorizaci budete přesměrováni zpět s kódem.</p>';
}

// Krok 2: Výměna kódu za token
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    $token_url = 'https://api.instagram.com/oauth/access_token';
    $post_data = [
        'client_id' => INSTAGRAM_APP_ID,
        'client_secret' => INSTAGRAM_APP_SECRET,
        'grant_type' => 'authorization_code',
        'redirect_uri' => REDIRECT_URI,
        'code' => $code
    ];
    
    $response = wp_remote_post($token_url, [
        'body' => $post_data
    ]);
    
    if (!is_wp_error($response)) {
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['access_token'])) {
            echo '<h2>Váš Access Token:</h2>';
            echo '<textarea rows="5" cols="80">' . $data['access_token'] . '</textarea>';
            echo '<p><strong>Zkopírujte tento token a vložte ho do ACF Options Page!</strong></p>';
            echo '<p><strong>POTÉ TENTO SOUBOR SMAŽTE!</strong></p>';
        } else {
            echo '<h2>Chyba:</h2>';
            echo '<pre>' . print_r($data, true) . '</pre>';
        }
    }
}
?>

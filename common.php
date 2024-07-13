<?php

session_start();

$base_url = 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/';

// Get your app's Client ID and Client Secret from Intuit's Developer Dashboard: https://developer.intuit.com/app/developer/dashboard

$config = [
    'client_id' => '', // App Client ID
    'client_secret' => '', // App Client Secret
    'csrf_token' => '', //  32 character unique/random string
    'openid_mode' => 'test',  // 'test' or 'live'
    'oauth_endpoint' => 'https://appcenter.intuit.com/connect/oauth2',
    'token_endpoint' => 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer',
    'revoke_endpoint' => 'https://developer.api.intuit.com/v2/oauth2/tokens/revoke',
    'userinfo_live_endpoint' => 'https://accounts.platform.intuit.com/v1/openid_connect/userinfo',
    'userinfo_test_endpoint' => 'https://sandbox-accounts.platform.intuit.com/v1/openid_connect/userinfo',
    'oauth_scope' => 'com.intuit.quickbooks.payment', // 'com.intuit.quickbooks.payment' or 'com.intuit.quickbooks.accounting'
    'oauth_redirect' => $base_url . 'connect.php',
    'openid_scope' => 'openid profile email phone address',
    'openid_redirect' => $base_url . 'connect.php?openid',
    'index_url' => $base_url,
];

function updateSession($result): void {
    $_SESSION['access_token'] = $result['access_token'];
    $_SESSION['refresh_token'] = $result['refresh_token'];
    $_SESSION['access_expires'] = date('Y-m-d H:i:s', time() + $result['expires_in']);
    $_SESSION['refresh_expires'] = date('Y-m-d H:i:s', time() + $result['x_refresh_token_expires_in']);
}

function clearSession(): void {
    unset(
        $_SESSION['access_token'],
        $_SESSION['refresh_token'],
        $_SESSION['access_expires'],
        $_SESSION['refresh_expires'],
        $_SESSION['user_info']
    );
}

function csrfToken(): string {
    return $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

function getAuthUrl(): string {
    global $config;
    
    $params = [
        'client_id' => $config['client_id'],
        'scope' => isset($_GET['openid']) ? $config['openid_scope'] : $config['oauth_scope'],
        'redirect_uri' => isset($_GET['openid']) ? $config['openid_redirect'] : $config['oauth_redirect'],
        'response_type' => 'code',
        'state' => csrfToken(),
    ];
    
    return $config['oauth_endpoint'] . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC1738);
}

function getAccessToken(): ?array {
    if (!empty($_GET['code'])) {
        global $config;
        
        $curl = curl_init($config['token_endpoint']);
        
        curl_setopt_array($curl, [
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Basic ' . base64_encode($config['client_id'] . ':' . $config['client_secret']),
            ],
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'authorization_code',
                'redirect_uri' => isset($_GET['openid']) ? $config['openid_redirect'] : $config['oauth_redirect'],
                'code' => $_GET['code'],
            ]),
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_RETURNTRANSFER => true,
        ]);
        
        if ($response = curl_exec($curl)) {
            curl_close($curl);
            
            $data = json_decode($response, true);
            
            if (!empty($data['error'])) {
                trigger_error(sprintf('%s (%s)', $data['error_description'], $data['error']), E_USER_WARNING);
                return null;
            }
            
            return $data;
        } elseif ($error = curl_error($curl)) {
            trigger_error($error, E_USER_WARNING);
        }
        
        curl_close($curl);
    }
	
	return null;
}

function refreshAccessToken(): ?array {
    if (!empty($_SESSION['refresh_token'])) {
        global $config;
        
        $curl = curl_init($config['token_endpoint']);
        
        curl_setopt_array($curl, [
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Basic ' . base64_encode($config['client_id'] . ':' . $config['client_secret']),
            ],
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'refresh_token',
                'refresh_token' => $_SESSION['refresh_token'],
            ]),
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_RETURNTRANSFER => true,
        ]);
        
        if ($response = curl_exec($curl)) {
            curl_close($curl);
            
            $data = json_decode($response, true);
            
            if (!empty($data['error'])) {
                trigger_error(sprintf('%s (%s)', $data['error_description'], $data['error']), E_USER_WARNING);
                return null;
            }
            
            return $data;
        } elseif ($error = curl_error($curl)) {
            trigger_error($error, E_USER_WARNING);
        }
        
        curl_close($curl);
    }
	
	return null;
}

function revokeAccessToken() {
    return revokeToken('access_token');
}

function revokeRefreshToken() {
    return revokeToken('refresh_token');
}

function revokeToken(string $key = 'access_token'): bool {
    $result = false;
    
    if (!empty($_SESSION[$key])) {
        global $config;
        
        $curl = curl_init($config['revoke_endpoint']);
        
        curl_setopt_array($curl, [
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Basic ' . base64_encode($config['client_id'] . ':' . $config['client_secret']),
            ],
            CURLOPT_POSTFIELDS => http_build_query([
                'token' => $_SESSION[$key],
            ]),
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_RETURNTRANSFER => true,
        ]);
        
        curl_exec($curl);
        
        $result = (!curl_error($curl) && curl_getinfo($curl, CURLINFO_HTTP_CODE) == 200);
        
        curl_close($curl);
    }
    
    return $result;
}

function getUserInfo(): ?array {
    if (!empty($_SESSION['access_token'])) {
        global $config;
        
        $curl = curl_init($config['openid_mode'] !== 'live' ? $config['userinfo_test_endpoint'] : $config['userinfo_live_endpoint']);
        
        curl_setopt_array($curl, [
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . $_SESSION['access_token'],
            ],
            CURLOPT_RETURNTRANSFER => true,
        ]);
        
        if ($response = curl_exec($curl)) {
            curl_close($curl);
            
            $data = json_decode($response, true);
            
            if (!empty($data['error'])) {
                trigger_error(sprintf('%s (%s)', $data['error_description'], $data['error']), E_USER_WARNING);
                return null;
            }
            
            return $data;
        } elseif ($error = curl_error($curl)) {
            trigger_error($error, E_USER_WARNING);
        }
        
        curl_close($curl);
    }
    
	return null;
}

<?php

require __DIR__ . '/common.php';

switch (true) {
    case isset($_GET['revoke']):
        revokeRefreshToken();
        clearSession();
        $_SESSION['warning'] = 'Token revoked.';
        break;
    case isset($_GET['refresh']):
        if ($result = refreshAccessToken()) {
            updateSession($result);
            $_SESSION['success'] = 'Token refreshed.';
        } else {
            clearSession();
            $_SESSION['error'] = 'Token refresh failed.';
        }
        break;
    default:
        if (!empty($_GET['error']) && $_GET['state'] == $config['csrf_token']) {
            $_SESSION['error'] = $_GET['error'];
            break;
        } elseif (empty($_GET['code'])) {
            clearSession();
            header('Location: ' . getAuthUrl());
            exit;
        } elseif ($_GET['state'] != $config['csrf_token']) {
            $_SESSION['error'] = 'State invalid.';
            break;
        } elseif ($result = getAccessToken()) {
            updateSession($result);
            $_SESSION['success'] = 'Connected.';
            if (isset($_GET['openid']) && $result = getUserInfo()) {
                $_SESSION['user_info'] = $result;
                $_SESSION['success'] = 'Connected with OpenID.';
            }
        }
}

header('Location: ' . $config['index_url']);
exit;

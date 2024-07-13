<?php

require __DIR__ . '/common.php';

?>
<!doctype html>
<html>
<head>
<title>QuickBooks Authentication</title>
<link rel="stylesheet" href="res/style.css">
</head>
<body>
<h1>QuickBooks Authentication</h1>
<?php foreach(['error', 'warning', 'success'] as $key) { ?>
    <?php if (!empty($_SESSION[$key])) { ?>
        <p class="alert <?php echo $key; ?>"><strong><?php echo ucfirst($key); ?>:</strong> <?php echo $_SESSION[$key]; unset($_SESSION[$key]); ?></p>
    <?php } ?>
<?php } ?>
<?php if (!empty($_SESSION['access_token'])) { ?>
    <h2>OAuth 2.0</h2>
    <hr>
    <p><strong>Access Token</strong>: <?php echo $_SESSION['access_token']; ?></p>
    <p><strong>Expires</strong>: <?php echo $_SESSION['access_expires']; ?></p>
    <p><a href="connect.php?refresh">Refresh</a></p>
    <hr>
    <p><strong>Refresh Token</strong>: <?php echo $_SESSION['refresh_token']; ?></p>
    <p><strong>Expires</strong>: <?php echo $_SESSION['refresh_expires']; ?></p>
    <p><a href="connect.php?revoke">Revoke</a></p>
    <hr>
    <h2>OpenID</h2>
    <?php if (!empty($_SESSION['user_info'])) { ?>
        <?php foreach ($_SESSION['user_info'] as $key => $value) { ?>
            <?php if ($key == 'address') { ?>
                <?php foreach ($value as $address_key => $address_value) { ?>
                    <strong><?php echo $address_key; ?>:</strong> <?php echo $address_value; ?><br>
                <?php } ?>
            <?php } else { ?>
                <strong><?php echo $key; ?>:</strong> <?php echo $value; ?><br>
            <?php } ?>
        <?php } ?>
    <?php } else { ?>
    <p><a href="connect.php?openid">Connect</a></p>
    <?php } ?>
<?php } else { ?>
    <?php if (empty($config['client_id']) || empty($config['client_secret']) || empty($config['csrf_token'])) { ?>
        <p class="alert error"><strong>Error:</strong> Missing <code>client_id</code>, <code>client_secret</code> or <code>csrf_token</code> in <code>common.php</code></p>
    <?php } else { ?>
        <p><a href="<?php echo $config['oauth_redirect']; ?>"><img src="res/oauth.png" alt="Sign into QuickBooks using OAuth 2.0"></a></p>
        <p><a href="<?php echo $config['openid_redirect']; ?>"><img src="res/openid.jpg" alt="Sign into QuickBooks using OpenID"></a></p>
    <?php } ?>
<?php } ?>
</body>
</html>

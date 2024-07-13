<?php

require __DIR__ . '/common.php';

?>
<!doctype html>
<html>
<head>
<title>QuickBooks Authentication</title>
<style>
body {
    background-color: #fff;
    font-family: sans-serif;
    font-size: 1rem;
    color: #000;
}
h1 {
    margin: .5rem 0;
}
code {
    background-color: rgba(255, 255, 255, 0.5);
    padding: 0 .125rem;
    border-radius: .25rem;
}
img {
    max-height:4rem;
}
hr {
    border: 0;
    border-top: 1px solid #eee;
}
pre {
    overflow: auto;
    font-size: .8rem;
    padding: .5rem;
    border-radius: .25rem;
    background-color: #eee;
}
.alert {
    padding: .5rem;
    border-radius: .25rem;
}
.alert.error {
    color: rgb(88, 21, 28);
    background-color: rgb(248, 215, 218);
    border: 1px solid rgb(241, 174, 181);
}
.alert.warning {
    color: rgb(102, 77, 3);
    background-color: rgb(255, 243, 205);
    border: 1px solid rgb(255, 230, 156);
}
.alert.success {
    color: rgb(10, 54, 34);
    background-color: rgb(209, 231, 221);
    border: 1px solid rgb(163, 207, 187);
}
</style>
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
    <?php if (empty($config['client_id']) || empty($config['client_secret']) || empty($config['random_state'])) { ?>
        <p class="alert error"><strong>Error:</strong> Missing <code>client_id</code>, <code>client_secret</code> or <code>random_state</code> in <code>common.php</code></p>
    <?php } else { ?>
        <p><a href="<?php echo $config['oauth_redirect']; ?>"><img src="images/oauth.png" alt="Sign into QuickBooks using OAuth 2.0"></a></p>
        <p><a href="<?php echo $config['openid_redirect']; ?>"><img src="images/openid.jpg" alt="Sign into QuickBooks using OpenID"></a></p>
    <?php } ?>
<?php } ?>
</body>
</html>

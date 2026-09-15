<?php
if (! defined('ABSPATH')) {
    exit;
}
$ver = defined('APOLLO_CHAT_VERSION') ? APOLLO_CHAT_VERSION : '1';
$mtime = static function (string $rel) use ($ver): string {
    $file = APOLLO_CHAT_PATH . $rel;
    $t    = is_readable($file) ? (string) filemtime($file) : '0';
    return $ver . '.' . $t;
};
$premium_css = esc_url(APOLLO_CHAT_URL . 'assets/css/chat-premium.css?v=' . $mtime('assets/css/chat-premium.css'));
$shell_css   = esc_url(APOLLO_CHAT_URL . 'assets/css/chat-shell.css?v=' . $mtime('assets/css/chat-shell.css'));
?>
<link rel="stylesheet" href="<?php echo $premium_css; ?>" fetchpriority="high">
<link rel="stylesheet" href="<?php echo $shell_css; ?>" fetchpriority="high">

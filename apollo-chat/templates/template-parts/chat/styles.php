<?php
if (! defined('ABSPATH')) {
    exit;
}
$premium_css = esc_url(APOLLO_CHAT_URL . 'assets/css/chat-premium.css?v=' . APOLLO_CHAT_VERSION);
?>
<link rel="stylesheet" href="<?php echo $premium_css; ?>" fetchpriority="high">
#!/usr/bin/env python3
"""Restore WordPress rewrite block in .htaccess and hard-flush rules."""
import paramiko

host = "ftp.rvalle.com.br"
user = "rvall260"
key_path = r"D:\dev\_apollo.rio.br\id_apll"
passphrase = "XouXuxa2026!"

# Write remote fixer script via sftp then execute
fixer = r'''<?php
$root = "/home1/rvall260/apollo.rio.br";
$ht = $root . "/.htaccess";
$bak = $root . "/.htaccess.bak-acesso-" . date("Ymd-His");

$current = is_readable($ht) ? file_get_contents($ht) : "";
if ($current === false) { $current = ""; }
copy($ht, $bak);

$epc = "";
if (preg_match("/# BEGIN NFD EPC.*?# END NFD EPC\\s*/s", $current, $m)) {
    $epc = trim($m[0]) . "\n\n";
}

$wp = <<<'HTA'
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
HTA;

// Preserve any non-EPC/non-WP custom bits carefully: strip old WP + EPC then rebuild.
$stripped = preg_replace("/# BEGIN WordPress.*?# END WordPress\\s*/s", "", $current);
$stripped = preg_replace("/# BEGIN NFD EPC.*?# END NFD EPC\\s*/s", "", $stripped);
$stripped = trim((string)$stripped);

$new = $epc . $wp . "\n";
if ($stripped !== "") {
    $new .= "\n" . $stripped . "\n";
}

file_put_contents($ht, $new);
echo "WROTE_HTACCESS bytes=" . strlen($new) . " bak=" . basename($bak) . "\n";
echo "HAS_WP=" . (strpos($new, "# BEGIN WordPress") !== false ? "1" : "0") . "\n";
echo "HAS_EPC=" . (strpos($new, "# BEGIN NFD EPC") !== false ? "1" : "0") . "\n";

// Boot WP and hard flush rewrite rules (+ clear repair markers so login self-heal can re-run if needed)
require $root . "/wp-load.php";
flush_rewrite_rules(true);
delete_option("apollo_login_htaccess_repair_2026_07");
update_option("apollo_login_flush_rewrites", defined("APOLLO_LOGIN_VERSION") ? APOLLO_LOGIN_VERSION : "1.0.34", false);
echo "FLUSHED=1\n";
echo "PERMALINK=" . get_option("permalink_structure") . "\n";
$rules = get_option("rewrite_rules");
echo "REWRITE_COUNT=" . (is_array($rules) ? count($rules) : 0) . "\n";
echo "ACESSO_RULE=" . (is_array($rules) && isset($rules["^(acesso|access|acessar|entrar)/?$"]) ? $rules["^(acesso|access|acessar|entrar)/?$"] : "MISSING") . "\n";

// verify htaccess after flush (WP may rewrite it)
$after = file_get_contents($ht);
echo "AFTER_HAS_WP=" . (strpos($after, "# BEGIN WordPress") !== false ? "1" : "0") . "\n";
echo "AFTER_HAS_EPC=" . (strpos($after, "# BEGIN NFD EPC") !== false ? "1" : "0") . "\n";
echo "AFTER_PREVIEW=\n" . substr($after, 0, 900) . "\n";
'''

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
pkey = paramiko.RSAKey.from_private_key_file(key_path, password=passphrase)
client.connect(host, username=user, pkey=pkey, timeout=45)
sftp = client.open_sftp()
remote = "/home1/rvall260/apollo.rio.br/wp-content/_tmp_fix_htaccess_acesso.php"
with sftp.file(remote, "w") as f:
    f.write(fixer)
sftp.close()

stdin, stdout, stderr = client.exec_command(
    f"php {remote}; echo '---CURL---'; "
    "curl -s -o /tmp/a.txt -w '%{http_code}' -A 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36' "
    "-H 'Accept: text/html,application/xhtml+xml' 'https://apollo.rio.br/acesso/'; echo; "
    "grep -o '<title>[^<]*' /tmp/a.txt | head -1; "
    "curl -s -o /tmp/r.txt -w '%{http_code}' -A 'Mozilla/5.0' -H 'Accept: text/html' 'https://apollo.rio.br/registre/'; echo; "
    "grep -o '<title>[^<]*' /tmp/r.txt | head -1; "
    f"rm -f {remote}",
    timeout=120,
)
out = stdout.read().decode("utf-8", "replace")
err = stderr.read().decode("utf-8", "replace")
path = r"D:\dev\_apollo.rio.br\plugins\_tmp_fix_htaccess_out.txt"
with open(path, "w", encoding="utf-8") as f:
    f.write(out)
    if err.strip():
        f.write("\nSTDERR\n" + err[:2000])
print(open(path, encoding="utf-8").read()[:2500])
client.close()

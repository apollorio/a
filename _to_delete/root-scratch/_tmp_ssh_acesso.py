#!/usr/bin/env python3
"""SSH probe: physical /acesso dir, rewrite rules, WP resolve."""
import paramiko

host = "ftp.rvalle.com.br"
user = "rvall260"
key_path = r"D:\dev\_apollo.rio.br\id_apll"
passphrase = "XouXuxa2026!"

cmds = r"""
set -e
ROOT=/home1/rvall260/apollo.rio.br
echo '=== LS acesso (physical?) ==='
ls -la "$ROOT/acesso" 2>&1 | head -20 || echo 'NO_PHYSICAL_DIR'
echo '=== LS wp-content/plugins/apollo-login version ==='
grep -E "Version:|APOLLO_LOGIN_VERSION" "$ROOT/wp-content/plugins/apollo-login/apollo-login.php" | head -5
echo '=== rewrite rules option contains acesso ==='
php -r '
define("ABSPATH","/home1/rvall260/apollo.rio.br/");
require "/home1/rvall260/apollo.rio.br/wp-load.php";
$rules = get_option("rewrite_rules");
$hits = [];
if (is_array($rules)) {
  foreach ($rules as $k=>$v) {
    if (stripos($k,"acesso")!==false || stripos((string)$v,"apollo_login")!==false) $hits[$k]=$v;
  }
}
echo "rewrite_count=".count((array)$rules)."\n";
echo "hits=".count($hits)."\n";
foreach (array_slice($hits,0,20,true) as $k=>$v) echo "$k => $v\n";
echo "permalink=".get_option("permalink_structure")."\n";
echo "login_flush=".get_option("apollo_login_flush_rewrites")."\n";
echo "login_ver=".(defined("APOLLO_LOGIN_VERSION")?APOLLO_LOGIN_VERSION:"?")."\n";
$page = get_page_by_path("acesso");
echo "wp_page_acesso=".($page?("ID ".$page->ID." status ".$page->post_status):"none")."\n";
'
echo '=== curl local acesso ==='
curl -sI -A 'Mozilla/5.0' -H 'Accept: text/html' 'https://apollo.rio.br/acesso/' | head -30
echo '=== curl body title ==='
curl -sL -A 'Mozilla/5.0' -H 'Accept: text/html' 'https://apollo.rio.br/acesso/' | head -c 1500 | tr '\n' ' ' | sed 's/<title/\'$'\n<title/g' | head -5
"""

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
pkey = paramiko.RSAKey.from_private_key_file(key_path, password=passphrase)
client.connect(host, username=user, pkey=pkey, timeout=45)
stdin, stdout, stderr = client.exec_command(cmds, timeout=120)
out = stdout.read().decode("utf-8", "replace")
err = stderr.read().decode("utf-8", "replace")
print(out)
if err.strip():
    print("STDERR:", err[:3000])
client.close()

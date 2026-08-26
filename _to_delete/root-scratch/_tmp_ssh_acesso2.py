#!/usr/bin/env python3
import paramiko

host = "ftp.rvalle.com.br"
user = "rvall260"
key_path = r"D:\dev\_apollo.rio.br\id_apll"
passphrase = "XouXuxa2026!"

cmds = r"""
ROOT=/home1/rvall260/apollo.rio.br
echo '=== .htaccess (full) ==='
sed -n '1,200p' "$ROOT/.htaccess"
echo ''
echo '=== user.ini / php.ini hits ==='
ls -la "$ROOT/.user.ini" "$ROOT/php.ini" 2>&1 | head -10
echo ''
echo '=== curl more paths ==='
for U in /acesso/ /registre/ /reset/ /verificar-email/ /evento/dismantle-2/ /feed/ /wp-login.php; do
  CODE=$(curl -s -o /tmp/curl_body.txt -w '%{http_code}' -A 'Mozilla/5.0' -H 'Accept: text/html,application/xhtml+xml' "https://apollo.rio.br$U")
  TITLE=$(grep -o '<title>[^<]*' /tmp/curl_body.txt | head -1)
  echo "$CODE $U $TITLE"
done
echo ''
echo '=== WP bootstrap template probe ==='
cd "$ROOT" && php -r '
$_SERVER["HTTP_HOST"]="apollo.rio.br";
$_SERVER["REQUEST_URI"]="/acesso/";
$_SERVER["HTTPS"]="on";
$_SERVER["REQUEST_METHOD"]="GET";
$_SERVER["SERVER_PROTOCOL"]="HTTP/1.1";
$_SERVER["HTTP_ACCEPT"]="text/html";
$_SERVER["HTTP_USER_AGENT"]="Mozilla/5.0";
define("WP_USE_THEMES", true);
ob_start();
try {
  require "wp-blog-header.php";
} catch (Throwable $e) {
  echo "THROW ".$e->getMessage()."\n";
}
$html=ob_get_clean();
echo "LEN=".strlen($html)."\n";
echo "HAS_TERMINAL=".(stripos($html,"Terminal de Acesso")!==false?"1":"0")."\n";
echo "HAS_404=".(stripos($html,"404")!==false?"1":"0")."\n";
if (preg_match("/<title[^>]*>(.*?)<\\/title>/is",$html,$m)) echo "TITLE=".$m[1]."\n";
echo substr(preg_replace("/\\s+/"," ",$html),0,200),"\n";
'
"""

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
pkey = paramiko.RSAKey.from_private_key_file(key_path, password=passphrase)
client.connect(host, username=user, pkey=pkey, timeout=45)
stdin, stdout, stderr = client.exec_command(cmds, timeout=180)
out = stdout.read().decode("utf-8", "replace")
err = stderr.read().decode("utf-8", "replace")
out_path = r"D:\dev\_apollo.rio.br\plugins\_tmp_ssh_acesso2_out.txt"
with open(out_path, "w", encoding="utf-8") as f:
    f.write(out)
    if err.strip():
        f.write("\n\nSTDERR:\n")
        f.write(err[:4000])
print("WROTE", out_path, "chars", len(out))
client.close()

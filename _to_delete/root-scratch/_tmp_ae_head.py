#!/usr/bin/env python3
"""Probe production HTML head for mandatory core.js order."""
import json
import paramiko

host = "ftp.rvalle.com.br"
user = "rvall260"
key_path = r"D:\dev\_apollo.rio.br\id_apll"
passphrase = "XouXuxa2026!"

# Fetch raw HTML via curl on server (simpler than bootstrapping WP).
cmd = r"""curl -sL -A 'ApolloHeadProbe/1' 'https://apollo.rio.br/evento/dismantle-2/?nocache=1' | php -r '
$html = stream_get_contents(STDIN);
if (!preg_match("/<head[^>]*>(.*?)<\\/head>/is", $html, $m)) { echo "NO_HEAD\n"; exit; }
$head = $m[1];
echo "HEAD_LEN=".strlen($head)."\n";
echo "HAS_CORE=".(strpos($head,"cdn.apollo.rio.br/v1.0.0/core.js?v=Random.x.1")!==false?"1":"0")."\n";
echo "HAS_APOLLO_CORE_ID=".(strpos($head,"id=\"apollo-core-js\"")!==false?"1":"0")."\n";
echo "HAS_PRE_ASSETS=".(stripos($head,"assets.apollo.rio.br")!==false?"1":"0")."\n";
echo "HAS_MANDATORY_COMMENT=".(strpos($head,"Mandatory Apollo CORE")!==false?"1":"0")."\n";
echo "VERSION_MARK=".(strpos($head,"apollo-single-event.css?v=1.2.9")!==false?"1":"0")."\n";
$pos = [
  "charset" => stripos($head, "charset"),
  "pre_assets" => stripos($head, "href=\"https://assets.apollo.rio.br\""),
  "pre_cdn" => stripos($head, "href=\"https://cdn.apollo.rio.br\""),
  "core" => stripos($head, "core.js"),
  "viewport" => stripos($head, "name=\"viewport\""),
];
echo "ORDER=".json_encode($pos)."\n";
$ok = $pos["charset"] !== false && $pos["pre_assets"] !== false && $pos["core"] !== false
  && $pos["charset"] < $pos["pre_assets"] && $pos["pre_assets"] < $pos["core"]
  && ($pos["viewport"] === false || $pos["core"] < $pos["viewport"]);
echo "COMPLIANT=".($ok?"1":"0")."\n";
$chunk = preg_replace("/\\s+/", " ", substr(ltrim($head), 0, 700));
echo "HEAD_PREFIX=".$chunk."\n";
'
"""

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
pkey = paramiko.RSAKey.from_private_key_file(key_path, password=passphrase)
client.connect(host, username=user, pkey=pkey, timeout=45)
stdin, stdout, stderr = client.exec_command(cmd, timeout=90)
out = stdout.read().decode("utf-8", "replace")
err = stderr.read().decode("utf-8", "replace")
print(out)
if err.strip():
    print("STDERR:", err[:2000])
client.close()

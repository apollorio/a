import paramiko

pkey = paramiko.RSAKey.from_private_key_file(
    r"D:\dev\_apollo.rio.br\id_apll", password="XouXuxa2026!"
)
client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(
    "ftp.rvalle.com.br",
    22,
    "rvall260",
    pkey=pkey,
    timeout=45,
    allow_agent=False,
    look_for_keys=False,
)

cmds = [
    # debug log from mu-plugin
    "ls -la /home1/rvall260/apollo.rio.br/wp-content/plugins/apollo-events/debug-e031aa.log 2>&1; tail -c 3000 /home1/rvall260/apollo.rio.br/wp-content/plugins/apollo-events/debug-e031aa.log 2>&1",
    # wp-cli if present
    "cd /home1/rvall260/apollo.rio.br && (wp post list --post_type=event --fields=ID,post_name,post_status --path=/home1/rvall260/apollo.rio.br 2>&1 | head -30)",
    # direct include via php with wp-load
    r"""cd /home1/rvall260/apollo.rio.br && php -d display_errors=1 -r '
require "wp-load.php";
$q = new WP_Query(["name"=>"dismantle-2","post_type"=>"event","post_status"=>"any"]);
echo "found=".$q->found_posts."\n";
if ($q->have_posts()) { $q->the_post(); echo "id=".get_the_ID()." title=".get_the_title()."\n"; echo "permalink=".get_permalink()."\n"; }
echo "parse_exists=".(function_exists("apollo_event_parse_date")?"1":"0")."\n";
echo "cpt=".(defined("APOLLO_EVENT_CPT")?APOLLO_EVENT_CPT:"no")."\n";
echo "mu_guard=". (file_exists(WP_CONTENT_DIR."/mu-plugins/apollo-events-helpers-guard.php")?"1":"0")."\n";
' 2>&1 | head -80""",
]

for i, cmd in enumerate(cmds):
    print(f"\n===== CMD {i} =====")
    stdin, stdout, stderr = client.exec_command(cmd, timeout=90)
    out = stdout.read().decode("utf-8", "replace")
    err = stderr.read().decode("utf-8", "replace")
    print(out[:4000])
    if err.strip():
        print("STDERR:", err[:1000])

client.close()
print("DONE")

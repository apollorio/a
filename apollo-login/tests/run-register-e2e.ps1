# Apollo Login registration E2E (simulates browser AJAX from /registre/)
# Run: powershell -ExecutionPolicy Bypass -File run-register-e2e.ps1

$BaseUrl = if ($env:APOLLO_E2E_BASE_URL) { $env:APOLLO_E2E_BASE_URL } else { 'http://aprio.local' }
$Pass = 0
$Fail = 0

function Write-Test {
    param([string]$Name, [bool]$Ok, [string]$Detail)
    if ($Ok) { $script:Pass++ } else { $script:Fail++ }
    $tag = if ($Ok) { 'PASS' } else { 'FAIL' }
    Write-Host "[$tag] $Name | $Detail"
}

function Get-PageNonce {
    param([string]$Path)
    $page = Invoke-WebRequest -Uri ($BaseUrl + $Path) -UseBasicParsing -SessionVariable session
    $parts = $page.Content -split '"nonce"\s*:\s*"', 2
    $nonce = if ($parts.Count -gt 1) { ($parts[1] -split '"', 2)[0] } else { '' }
    return @{ Status = $page.StatusCode; Nonce = $nonce; Session = $session; HasForm = ($page.Content -like '*register-form*'); Content = $page.Content }
}

function New-ValidCpf {
    $n = 1..9 | ForEach-Object { Get-Random -Minimum 0 -Maximum 10 }
    $sum = 0
    for ($i = 0; $i -lt 9; $i++) { $sum += $n[$i] * (10 - $i) }
    $d1 = if (($sum % 11) -lt 2) { 0 } else { 11 - ($sum % 11) }
    $sum2 = 0
    for ($i = 0; $i -lt 9; $i++) { $sum2 += $n[$i] * (11 - $i) }
    $sum2 += $d1 * 2
    $d2 = if (($sum2 % 11) -lt 2) { 0 } else { 11 - ($sum2 % 11) }
    return (-join $n) + [string]$d1 + [string]$d2
}

function New-QuizToken {
    param($Session)
    $token = 'e2e-' + [guid]::NewGuid().ToString('N').Substring(0, 12)
    $body = @{ stage = 'pattern'; answers = @{ q1 = 'a' }; token = $token } | ConvertTo-Json -Compress
    try {
        $r = Invoke-WebRequest -Uri ($BaseUrl + '/wp-json/apollo/v1/quiz/submit') -Method POST -Body $body -ContentType 'application/json' -WebSession $Session -UseBasicParsing
        $json = $r.Content | ConvertFrom-Json
        if ($json.token) { return [string]$json.token }
    }
    catch {
        Write-Host "WARN: quiz/submit failed, using local token only: $($_.Exception.Message)"
    }
    return $token
}

function Get-DefaultBirthFields {
    $born = (Get-Date).AddYears(-25)
    return @{
        birth_date = $born.ToString('yyyy-MM-dd')
        bday_day = $born.Day
        bday_month = $born.Month
        bday_year = $born.Year
        party_role = 'dancing_floor'
    }
}

function Get-DefaultPhoneFields {
    $suffix = (Get-Random -Minimum 100000000 -Maximum 999999999)
    return @{
        phone             = "5511$suffix"
        phone_request_id  = 'e2e-local-bypass'
        phone_verified    = '1'
    }
}

function Invoke-RegAjax {
    param($Session, [string]$Nonce, [hashtable]$Fields, [string]$QuizToken)
    $birth = Get-DefaultBirthFields
    $phone = Get-DefaultPhoneFields
    $payload = @{
        action            = 'apollo_register'
        nonce             = $Nonce
        quiz_passed       = '1'
        terms_accepted    = '1'
        marketing_opt_in  = '0'
        clubber_universe  = 'both'
        apollo_quiz_token = $QuizToken
        birth_date        = $birth.birth_date
        bday_day          = $birth.bday_day
        bday_month        = $birth.bday_month
        bday_year         = $birth.bday_year
        party_role        = $birth.party_role
        phone             = $phone.phone
        phone_request_id  = $phone.phone_request_id
        phone_verified    = $phone.phone_verified
    }
    foreach ($k in $Fields.Keys) { $payload[$k] = $Fields[$k] }
    try {
        $r = Invoke-WebRequest -Uri ($BaseUrl + '/wp-admin/admin-ajax.php') -Method POST -Body $payload -WebSession $Session -UseBasicParsing
        return @{ Status = [int]$r.StatusCode; Json = ($r.Content | ConvertFrom-Json); Raw = $r.Content }
    }
    catch {
        $resp = $_.Exception.Response
        $raw = ''
        if ($resp) {
            $reader = New-Object System.IO.StreamReader($resp.GetResponseStream())
            $raw = $reader.ReadToEnd()
        }
        $json = $null
        try { $json = $raw | ConvertFrom-Json } catch {}
        return @{ Status = [int]$resp.StatusCode.value__; Json = $json; Raw = $raw }
    }
}

Write-Host ''
Write-Host '=== APOLLO REGISTER E2E (via /registre/) ==='
Write-Host "Base URL: $BaseUrl"
Write-Host ''

$regPage = Get-PageNonce '/registre/'
Write-Test '/registre/ loads with register form + nonce' ($regPage.Status -eq 200 -and $regPage.HasForm -and $regPage.Nonce) "HTTP $($regPage.Status)"
Write-Test '/registre/ has birthday fields' ($regPage.Content -like '*bday-field*' -and $regPage.Content -like '*party-role-field*') 'bday + party UI'
Write-Test '/registre/ has step 1a/1b panels' ($regPage.Content -like '*register-step-1a*' -and $regPage.Content -like '*register-step-1b*') 'split registration steps'
Write-Test '/registre/ has phone verify UI' ($regPage.Content -like '*reg-phone-field*' -and $regPage.Content -like '*phone_verified*') 'telegram phone embed'

$registroPage = Get-PageNonce '/registro/'
Write-Test '/registro/ is NOT Apollo register (expect no nonce/form)' (-not $registroPage.HasForm) "HTTP $($registroPage.Status) hasForm=$($registroPage.HasForm) note=use /registre/"

$nonce = $regPage.Nonce
$ws = $regPage.Session
$ts = Get-Date -Format 'yyyyMMddHHmmss'
$quizToken = New-QuizToken $ws

$igP = "e2ep$ts"
$emP = "e2ep$ts@apollo.local"
$rPass = Invoke-RegAjax $ws $nonce @{
    nome = 'E2E Passport'; instagram = $igP; email = $emP; senha = 'TestPass123!'
    doc_type = 'passport'; passport = "PX$ts"
} $quizToken
Write-Test 'Passport registration + email_sent' (
    $rPass.Status -eq 200 -and $rPass.Json.success -and $rPass.Json.data.email_sent -and $rPass.Json.data.user_id
) "user_id=$($rPass.Json.data.user_id) email_sent=$($rPass.Json.data.email_sent)"

$igC = "e2ec$ts"
$emC = "e2ec$ts@apollo.local"
$cpf = New-ValidCpf
$quizToken2 = New-QuizToken $ws
$rCpf = Invoke-RegAjax $ws $nonce @{
    nome = 'E2E CPF'; instagram = $igC; email = $emC; senha = 'TestPass123!'
    doc_type = 'cpf'; cpf = $cpf
} $quizToken2
Write-Test 'CPF registration + email_sent' (
    $rCpf.Status -eq 200 -and $rCpf.Json.success -and $rCpf.Json.data.email_sent
) "cpf=$cpf user_id=$($rCpf.Json.data.user_id) email_sent=$($rCpf.Json.data.email_sent)"

$quizToken3 = New-QuizToken $ws
$rDup = Invoke-RegAjax $ws $nonce @{
    nome = 'Dup'; instagram = ($igP + 'x'); email = $emP; senha = 'TestPass123!'
    doc_type = 'passport'; passport = "DUP$ts"
} $quizToken3
Write-Test 'Duplicate email returns 400 validation_failed' (
    $rDup.Status -eq 400 -and $rDup.Json.data.code -eq 'validation_failed'
) $($rDup.Json.data.message)

$quizToken4 = New-QuizToken $ws
$rDoc = Invoke-RegAjax $ws $nonce @{
    nome = 'NoDoc'; instagram = "nd$ts"; email = "nd$ts@apollo.local"; senha = 'TestPass123!'
    doc_type = ''; cpf = ''; passport = ''
} $quizToken4
Write-Test 'Empty document returns 400' ($rDoc.Status -eq 400) $($rDoc.Json.data.message)

$quizToken5 = New-QuizToken $ws
$rYoung = Invoke-RegAjax $ws $nonce @{
    nome = 'Young'; instagram = "yg$ts"; email = "yg$ts@apollo.local"; senha = 'TestPass123!'
    doc_type = 'passport'; passport = "YG$ts"
    birth_date = (Get-Date).AddYears(-10).ToString('yyyy-MM-dd')
    bday_day = (Get-Date).AddYears(-10).Day
    bday_month = (Get-Date).AddYears(-10).Month
    bday_year = (Get-Date).AddYears(-10).Year
} $quizToken5
Write-Test 'Under-18 birthday returns 400' ($rYoung.Status -eq 400) $($rYoung.Json.data.message)

$quizToken6 = New-QuizToken $ws
$rParty = Invoke-RegAjax $ws $nonce @{
    nome = 'NoParty'; instagram = "np$ts"; email = "np$ts@apollo.local"; senha = 'TestPass123!'
    doc_type = 'passport'; passport = "NP$ts"; party_role = ''
} $quizToken6
Write-Test 'Missing party_role returns 400' ($rParty.Status -eq 400) $($rParty.Json.data.message)

try {
    Invoke-WebRequest -Uri ($BaseUrl + '/wp-admin/admin-ajax.php') -Method POST -Body @{
        action = 'apollo_register'; nonce = $nonce; nome = 'Q'; instagram = "q$ts"
        email = "q$ts@a.l"; senha = 'TestPass123!'; doc_type = 'passport'; passport = "Q$ts"
        quiz_passed = '0'; terms_accepted = '1'
        birth_date = '1990-01-15'; party_role = 'front_row'
    } -WebSession $ws -UseBasicParsing | Out-Null
    Write-Test 'Missing quiz_passed blocked' $false 'expected 400'
}
catch {
    $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
    $j = $reader.ReadToEnd() | ConvertFrom-Json
    Write-Test 'Missing quiz_passed blocked' ($j.data.code -eq 'validation_failed') $j.data.message
}

$quizToken7 = New-QuizToken $ws
try {
    Invoke-WebRequest -Uri ($BaseUrl + '/wp-admin/admin-ajax.php') -Method POST -Body @{
        action = 'apollo_register'; nonce = $nonce; nome = 'BadQuiz'; instagram = "bq$ts"
        email = "bq$ts@a.l"; senha = 'TestPass123!'; doc_type = 'passport'; passport = "BQ$ts"
        quiz_passed = '1'; terms_accepted = '1'
        birth_date = '1990-01-15'; party_role = 'front_row'
        apollo_quiz_token = 'invalid-token-not-in-transient'
    } -WebSession $ws -UseBasicParsing | Out-Null
    Write-Test 'Invalid quiz token blocked' $false 'expected 400'
}
catch {
    $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
    $j = $reader.ReadToEnd() | ConvertFrom-Json
    Write-Test 'Invalid quiz token blocked' ($j.data.code -eq 'validation_failed') $j.data.message
}

try {
    Invoke-WebRequest -Uri ($BaseUrl + '/wp-admin/admin-ajax.php') -Method POST -Body @{
        action = 'apollo_register'; nonce = 'invalid'; nome = 'X'; email = 'x@y.z'
        instagram = 'x'; senha = 'TestPass123!'; quiz_passed = '1'; terms_accepted = '1'
        birth_date = '1990-01-15'; party_role = 'front_row'
    } -WebSession $ws -UseBasicParsing | Out-Null
    Write-Test 'Invalid nonce returns 403' $false 'expected 403'
}
catch {
    Write-Test 'Invalid nonce returns 403' ($_.Exception.Response.StatusCode.value__ -eq 403) 'HTTP 403'
}

$uid = $rPass.Json.data.user_id
$logPath = 'C:\Users\User\Local Sites\aprio\app\public\wp-content\debug.log'
if (Test-Path $logPath) {
    $hit = Select-String -Path $logPath -Pattern "user_id=$uid sent=true" -SimpleMatch | Select-Object -Last 1
    Write-Test 'debug.log confirms verification sent' ($null -ne $hit) $(if ($hit) { $hit.Line.Trim() } else { 'missing' })
}

try {
    $mp = Invoke-WebRequest -Uri 'http://localhost:10005/api/v1/messages' -UseBasicParsing -TimeoutSec 5
    $total = ($mp.Content | ConvertFrom-Json).total
    Write-Test 'Mailpit capturing SMTP' ($total -gt 0) "total_messages=$total (check http://localhost:10005)"
}
catch {
    Write-Test 'Mailpit capturing SMTP' $false $_.Exception.Message
}

Write-Host ''
Write-Host "SUMMARY: $Pass passed, $Fail failed / $($Pass + $Fail) total"
Write-Host ''
if ($Fail -gt 0) { exit 1 }
exit 0

# Join chain

1. session WORKING else `session_down`
2. BR candidates 12 and 13 — never add 9 to landline
3. GET check-exists both — trust returned chatId
4. store `_apollo_wa_jid` + `_apollo_wa_lid`
5. PUT contact twice, 3s pause
6. wait 5s
7. POST participants/add
8. 200 no Error → `added`
9. 409 → `already`
10. Error 403 + AddRequest.Code → DM `https://chat.whatsapp.com/{Code}` → `invited`
11. 403 no Code → GET invite-code → `invited`
12. never loop add; never treat body 403 as logout

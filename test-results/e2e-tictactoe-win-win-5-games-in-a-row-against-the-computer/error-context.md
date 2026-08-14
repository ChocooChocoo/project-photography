# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: e2e\tictactoe-win.spec.js >> win 5 games in a row against the computer
- Location: e2e\tictactoe-win.spec.js:79:1

# Error details

```
Error: board never reset after restart
```

# Page snapshot

```yaml
- generic [active]:
  - link [ref=e1] [cursor=pointer]:
    - /url: https://neave.com/
  - generic [ref=e2] [cursor=pointer]
  - generic [ref=e8]:
    - generic [ref=e9]:
      - generic [ref=e10] [cursor=pointer]
      - generic [ref=e12] [cursor=pointer]
      - generic [ref=e13] [cursor=pointer]
      - generic [ref=e14] [cursor=pointer]
      - generic [ref=e16] [cursor=pointer]
      - generic [ref=e18] [cursor=pointer]
      - generic [ref=e19] [cursor=pointer]
      - generic [ref=e21] [cursor=pointer]
      - generic [ref=e23] [cursor=pointer]
    - generic [ref=e24] [cursor=pointer]
  - generic [ref=e25] [cursor=pointer]:
    - paragraph [ref=e26]:
      - text: Player ()
      - generic [ref=e28]: "1"
    - paragraph [ref=e29]:
      - text: Tie
      - generic [ref=e30]: "0"
    - paragraph [ref=e31]:
      - text: Computer ()
      - generic [ref=e33]: "0"
    - paragraph [ref=e38]: 1P
  - insertion [ref=e40]:
    - iframe [ref=e42]:
      - generic [ref=f3e1]:
        - generic [ref=f3e3]:
          - link:
            - /url: https://googleads.g.doubleclick.net/aclk?sa=l&ai=CGfEmg9R-atLNAp2i29gP9K_xQMGtpLOIAZ3QlOfTFZ7zwbKKDhABILf3_gJg4YSAgOwZoAG_l93EKsgBAqkCieoJXdIagT6oAwHIA8kEqgTaAU_Q0ju2u7aQkl26fDIRjKMYJwsw7xWYxA9U0lZzmRam5qLXSEODxPAKdvhR8XZHHKNfxdpgMGeH4MDs7CXTSfRctXlfJHh5ckzyROpESNdt2jyL-_Hm0tU2LLB8W69IWMHLLvayr9jrr4z19FzrWnaFWN-1qGv_9AdirBlJGUqZX4mY_pBujvMwbUujGoyIWW--72pxh-jwyKstR4hWYaR1PwrtWmB8ZpbEn0w7MNutmJ78ZjEHtGv66qhU4w-YI-SOAqnWPnxHG6PqdeKU_4QTyIMxPIKHQsWhwATdjqSr3AWIBf6OkqhRoAYCgAe_z62kBagHp8yxAqgH4tixAqgHpr4bqAfMzrECqAfz0RuoB5bYG6gHqpuxAqgH_uixAqgHjs4bqAeT2BuoB_DgG6gH7paxAqgH_p6xAqgHr76xAqgHn-GxAqgHpuuxAqgH1ckbqAfZtrECqAeaBqgH_56xAqgH35-xAqgH-MKxAqgH-8KxAtgHAdIIMwiAYRABGJ8DMgiKgoCAgICACDoPgECAwICAgICogAKog4AQSL39wTpY-_ixsdyflgNgAbEJ_0U9Jx06O7CACgGYCwHICwGiDAOQAQGqDQJQSMgNAeoNEwiloLKx3J-WAxUd0RYFHfRXHAjwDQKIDgnYEwzQFQGYFgHKFgIKAPgWAYAXAbIXEBgBKgoxMTMzNDQ5OTQwUAa6FwI4AaoYFwkAAAAAwCcSQRIKMTEzMzQ0OTk0MBgBshgJEgKwUxgCIgEA0BgBwhkCCAE&ae=1&gclid=EAIaIQobChMIkpuysdyflgMVHdEWBR30VxwIEAEYASAAEgIzufD_BwE&num=1&cid=CAQShgIAEQoqga-YWPnN8HgmMDBb9Ce2LMtN3GYzGzLlS68Xq5Qurmu-owiTMVFlGRfWlJKgMu7Og9ApYGXgmWH6YUVKNJ2YWoli-ETGGTu_w6ini0p34tEnDGG-ZIkBzoZIHryAgQsa7D9AqKBbN_O2N8XtgQSBywW314pG3N9drZjTkkoVit5aRpUpDp66wgx3uvzJzPgSqPE17YtDsBZx7MN3WmF6-1Imu9jzhJ59WADJ65uNCqwVBucX6w3SylFzfqmLCX6skjwBGJ89jTnp31LQuk19BSY-fK8cV2Dpjcm9GnHpGRcbZ-Q6Qs4kstibzL4NSj4F-sPTcVrXyvDNBKAv6CCc-j50GAE&sig=AOD64_3uddqj_Ndj97lcusw0gighbhCrkA&client=ca-pub-9316345695176880&rf=2&nb=2&adurl=https://www.enjoysgame.com/extension%3Fgad_source%3D5%26gad_campaignid%3D21827454846%26gclid%3DEAIaIQobChMIkpuysdyflgMVHdEWBR30VxwIEAEYASAAEgIzufD_BwE
          - generic [ref=f3e4] [cursor=pointer]
          - button [ref=f3e9] [cursor=pointer]
        - iframe
  - insertion [ref=e44]:
    - iframe [ref=e46]:
      - generic [ref=f4e1]:
        - generic [ref=f4e3]:
          - link:
            - /url: https://googleads.g.doubleclick.net/aclk?sa=l&ai=CwMlrg9R-auGyAteM29gP_dHesQTBraSziAGd0JTn0xWe88Gyig4QASC39_4CYOGEgIDsGaABv5fdxCrIAQKpApSKQKbgGoE-qAMByAPJBKoE2gFP0DB1d4oGPCvATpNKtAD7ru2pFUKZR-p8JnrGmvTwGpR6akTUGcI9xhU5lIG8iAQOZLQPI6-Ndpy6KybiokjprAmPzbW-2V2Ojdzp99TIW75EO17GOsBuWdaQEpHokyhnWjW76qYTW_y-1QUtR0-mgFYNxXDgty1UsRAZ-dM2cujvLgcqhfdFmL0mTUQjEwpt_d4fx5Z16eQxf9fNyOd6SY5XuDDqxIH1_oYI5JYcUMW9R3eMtJ5ajzztl5HDKly_QJhLSYy6pO3CFnVbLknnVbBLjL3mgEn0HsAE3Y6kq9wFiAX-jpKoUaAGAoAHv8-tpAWoB6fMsQKoB-LYsQKoB6a-G6gHzM6xAqgH89EbqAeW2BuoB6qbsQKoB_7osQKoB47OG6gHk9gbqAfw4BuoB-6WsQKoB_6esQKoB6--sQKoB5_hsQKoB6brsQKoB9XJG6gH2baxAqgHmgaoB_-esQKoB9-fsQKoB_jCsQKoB_vCsQLYBwHSCDMIgGEQARifAzIIioKAgICAgAg6D4BAgMCAgICAqIACqIOAEEi9_cE6WN7hsbHcn5YDYAGxCf9FPScdOjuwgAoBmAsByAsBogwDkAEBqg0CUEjIDQHqDRMInoiysdyflgMVV8YWBR39qDdG8A0CiA4J2BMM0BUBmBYByhYCCgD4FgGAFwGyFxAYASoKMTEzMzQ0OTk0MFAGuhcCOAGqGBcJAAAAAACTB0ESCjExMzM0NDk5NDAYAbIYCRICsFMYAiIBANAYAcIZAggB&ae=1&gclid=EAIaIQobChMIoYCysdyflgMVV8YWBR39qDdGEAEYASAAEgJUZvD_BwE&num=1&cid=CAQShwIAEQoqgSjQ7H9aEVXNKyfoBgMMiz3EbgD4hulEjuQwuywS6Q35KlGKCp3QsAdSNRE9Z3l4VLnb2TlnEjiNVSBOrP-muphMYl49zjqRmtbvrxf56kt99QN3VIChtww5nsLoaZGrAchiZJ98RdksWqABXFmw17GOKq1Pp67KLbqc6jXgs502RDjZkhso31fhiY3fIzyppL0ld2LOGixT_DS4_3wW93fzFPs2xHmMa9fk3cVuXs7iVjHGzfSY2XIK_zt5gEfcX2JN4IsTQ1flsg40h6SxRlNSV1qb2fQNEo-hzAbpQlD3-NT-xizGQnR-pAn2ygWstie3zF987Puzdi3gihJzQnY-4xgB&sig=AOD64_3-yVf4s_PwPEs6pqK_TTnExSyN8A&client=ca-pub-9316345695176880&rf=2&nb=2&adurl=https://www.enjoysgame.com/extension%3Fgad_source%3D5%26gad_campaignid%3D21827454846%26gclid%3DEAIaIQobChMIoYCysdyflgMVV8YWBR39qDdGEAEYASAAEgJUZvD_BwE
          - generic [ref=f4e4] [cursor=pointer]
          - button [ref=f4e9] [cursor=pointer]
        - iframe
```

# Test source

```ts
  1   | import { test, expect } from '@playwright/test';
  2   | 
  3   | const LINES = [[0, 1, 2], [3, 4, 5], [6, 7, 8], [0, 3, 6], [1, 4, 7], [2, 5, 8], [0, 4, 8], [2, 4, 6]];
  4   | const X = -1, O = 1;
  5   | 
  6   | function winner(b) {
  7   |   for (const line of LINES) {
  8   |     const s = b[line[0]] + b[line[1]] + b[line[2]];
  9   |     if (s === 3) return O;
  10  |     if (s === -3) return X;
  11  |   }
  12  |   return b.every(v => v !== 0) ? 0 : null;
  13  | }
  14  | 
  15  | function score(b) {
  16  |   const w = winner(b);
  17  |   return w === X ? 1 : w === O ? -1 : 0;
  18  | }
  19  | 
  20  | function minimax(b, player) {
  21  |   if (winner(b) !== null) return score(b);
  22  |   let best = player === X ? -Infinity : Infinity;
  23  |   for (let i = 0; i < 9; i++) {
  24  |     if (b[i] !== 0) continue;
  25  |     b[i] = player;
  26  |     const s = minimax(b, -player);
  27  |     b[i] = 0;
  28  |     best = player === X ? Math.max(best, s) : Math.min(best, s);
  29  |   }
  30  |   return best;
  31  | }
  32  | 
  33  | function bestMove(b) {
  34  |   let best = -Infinity, moves = [];
  35  |   for (let i = 0; i < 9; i++) {
  36  |     if (b[i] !== 0) continue;
  37  |     b[i] = X;
  38  |     const s = minimax(b, O);
  39  |     b[i] = 0;
  40  |     if (s > best) { best = s; moves = [i]; }
  41  |     else if (s === best) moves.push(i);
  42  |   }
  43  |   return moves[Math.floor(Math.random() * moves.length)];
  44  | }
  45  | 
  46  | async function readBoard(page) {
  47  |   return page.locator('.board .square').evaluateAll(sqs =>
  48  |     sqs.map(sq => {
  49  |       const cls = sq.querySelector('div').className;
  50  |       if (cls.includes('x')) return -1;
  51  |       if (cls.includes('o')) return 1;
  52  |       return 0;
  53  |     })
  54  |   );
  55  | }
  56  | 
  57  | async function waitForChange(page, prev, label, timeoutMs = 5000) {
  58  |   const start = Date.now();
  59  |   while (Date.now() - start < timeoutMs) {
  60  |     const b = await readBoard(page);
  61  |     if (JSON.stringify(b) !== JSON.stringify(prev)) return b;
  62  |     await page.waitForTimeout(100);
  63  |   }
  64  |   const b = await readBoard(page);
  65  |   throw new Error(`${label}: no board change within ${timeoutMs}ms. prev=${JSON.stringify(prev)} cur=${JSON.stringify(b)}`);
  66  | }
  67  | 
  68  | async function waitForEmpty(page) {
  69  |   const start = Date.now();
  70  |   while (Date.now() - start < 5000) {
  71  |     if ((await readBoard(page)).every(v => v === 0)) return;
  72  |     await page.waitForTimeout(100);
  73  |   }
> 74  |   throw new Error('board never reset after restart');
      |         ^ Error: board never reset after restart
  75  | }
  76  | 
  77  | test.setTimeout(600000);
  78  | 
  79  | test('win 5 games in a row against the computer', async ({ page }) => {
  80  |   await page.goto('https://playtictactoe.org/');
  81  |   await page.waitForTimeout(700);
  82  | 
  83  |   let streak = 0, games = 0;
  84  | 
  85  |   while (streak < 5 && games < 100) {
  86  |     let b = await readBoard(page);
  87  |     let w = winner(b);
  88  | 
  89  |     if (w !== null) {
  90  |       const result = w === X ? 'WIN' : w === O ? 'LOSS' : 'TIE';
  91  |       games++;
  92  |       if (result === 'WIN') streak++; else streak = 0;
  93  |       console.log(`Game ${games}: ${result} (streak: ${streak})`);
  94  |       await page.waitForTimeout(500);
  95  |       await page.locator('.restart').click({ force: true });
  96  |       await waitForEmpty(page);
  97  |       continue;
  98  |     }
  99  | 
  100 |     const xCount = b.filter(v => v === -1).length;
  101 |     const oCount = b.filter(v => v === 1).length;
  102 | 
  103 |     if (xCount === oCount) {
  104 |       const move = bestMove(b);
  105 |       await page.locator('.board .square').nth(move).click({ force: true });
  106 |       b = await waitForChange(page, b, `after my move (cell ${move})`);
  107 |       w = winner(b);
  108 |       if (w !== null) continue;
  109 |       b = await waitForChange(page, b, 'AI response');
  110 |     } else {
  111 |       b = await waitForChange(page, b, 'AI move');
  112 |     }
  113 |   }
  114 | 
  115 |   const score1 = await page.locator('.player1 .score').innerText();
  116 |   const score2 = await page.locator('.player2 .score').innerText();
  117 |   console.log(`Final score - Player(X): ${score1}, Computer(O): ${score2}`);
  118 |   expect(streak).toBe(5);
  119 | });
  120 | 
```
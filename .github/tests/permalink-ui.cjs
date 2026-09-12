/* Run with NODE_PATH pointing to Playwright and the disposable PHP router on port 18871. */
const { chromium } = require('playwright');
const assert = require('node:assert/strict');

(async () => {
    const browser = await chromium.launch({ channel: 'chrome', headless: true });
    try {
        for (const production of [false, true]) {
            const context = await browser.newContext({ acceptDownloads: true });
            const page = await context.newPage();
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.goto('http://127.0.0.1:18871/' + (production ? '?production=1' : ''));
            const apply = page.locator('#serbian-transliteration-tools-transliterate-permalinks');
            assert(await apply.isDisabled(), 'Apply requires confirmation');
            await page.evaluate(() => localStorage.setItem('rstr-permalinks:' + RSTR.permalink_storage, JSON.stringify({ job: 'stale' })));
            await page.reload();
            assert(await page.locator('#rstr-permalink-dry-run').isEnabled(), 'invalid saved state is discarded automatically');
            await page.locator('.tools-transliterate-permalinks-post-types').evaluateAll(inputs => inputs.forEach(input => { input.checked = false; }));
            await page.locator('#rstr-permalink-dry-run').click();
            await page.waitForFunction(() => document.querySelector('#rstr-permalink-result').textContent.includes('Select at least'));
            await page.locator('.tools-transliterate-permalinks-taxonomies[value="rstr_tree"]').check();
            // Simulate a lost response, then reload and retry the same operation token.
            let interrupted = false;
            await page.route('**/wp-admin/admin-ajax.php', async route => {
                if (!interrupted) { interrupted = true; await route.abort(); } else { await route.continue(); }
            });
            await page.locator('#rstr-permalink-dry-run').click();
            await page.waitForFunction(() => document.querySelector('#rstr-permalink-result').classList.contains('notice-error'));
            const stored = await page.evaluate(() => JSON.parse(localStorage.getItem('rstr-permalinks:' + RSTR.permalink_storage)));
            assert(stored.job && stored.mode === 'dry_run');
            await page.reload();
            await page.locator('#rstr-permalink-resume').click();
            await page.waitForFunction(() => !document.querySelector('#rstr-permalink-report').hidden, { timeout: 90000 });
            assert.match(await page.locator('#rstr-permalink-result').innerText(), /Dry run complete/);
            assert(await page.locator('#rstr-permalink-csv').isHidden());
            assert(await page.locator('#rstr-permalink-dry-run').isEnabled());
            assert(await apply.isDisabled());
            assert((await page.locator('#rstr-permalink-preview tbody tr').count()) <= 50);
            const reportResponse = await page.request.get(await page.locator('#rstr-permalink-report').getAttribute('href'));
            assert.equal(reportResponse.status(), 200);
            assert.match(reportResponse.headers()['content-type'], /text\/csv/);
            assert.match(await reportResponse.text(), /^object_type,object_id,/);
            const downloadPromise = page.waitForEvent('download');
            await page.locator('#rstr-permalink-report').click();
            const download = await downloadPromise;
            assert.equal(download.suggestedFilename(), 'permalink-report.csv');
            const downloadFailure = await download.failure();
            if (downloadFailure) console.log('Browser download storage: ' + downloadFailure + '; authenticated HTTP CSV response verified separately.');
            await page.locator('#rstr-permalink-reset').click();
            assert.equal(await page.evaluate(() => localStorage.getItem('rstr-permalinks:' + RSTR.permalink_storage)), null, 'saved operation can be discarded');
            await page.locator('#serbian-transliteration-tools-check').check();
            await apply.click();
            await page.waitForFunction(() => document.querySelector('#rstr-permalink-result').textContent.includes('Migration complete'), { timeout: 90000 });
            assert.deepEqual(errors, [], 'No JavaScript errors');
            await context.close();
            console.log('PASS: ' + (production ? 'production' : 'source') + ' admin workflow, empty selection, confirmation, interrupted request, reload/resume, dry run, report download and apply');
        }
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });

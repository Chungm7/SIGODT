const { test, describe } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

describe('Roboto Typography & Local Assets Standardization', () => {
  const rootDir = path.join(__dirname, '..');
  const robotoDir = path.join(rootDir, 'public/lib/roboto');
  const fontFilesDir = path.join(robotoDir, 'font-files');

  test('Roboto CSS file exists and contains standard weights and font-display: swap', () => {
    const cssPath = path.join(robotoDir, 'roboto.css');
    assert.ok(fs.existsSync(cssPath), 'roboto.css should exist');

    const cssContent = fs.readFileSync(cssPath, 'utf8');
    assert.match(cssContent, /font-family:\s*'Roboto'/);
    assert.match(cssContent, /font-weight:\s*300/);
    assert.match(cssContent, /font-weight:\s*400/);
    assert.match(cssContent, /font-weight:\s*500/);
    assert.match(cssContent, /font-weight:\s*700/);
    assert.match(cssContent, /font-style:\s*italic/);
    assert.match(cssContent, /font-display:\s*swap;/);
  });

  test('All five Roboto WOFF2 font files exist and are valid WOFF2 binaries', () => {
    const expectedFonts = [
      'Roboto-Light.woff2',
      'Roboto-Regular.woff2',
      'Roboto-Italic.woff2',
      'Roboto-Medium.woff2',
      'Roboto-Bold.woff2',
    ];

    const woff2Magic = Buffer.from([0x77, 0x4f, 0x46, 0x32]); // "wOF2"

    for (const fontName of expectedFonts) {
      const fontPath = path.join(fontFilesDir, fontName);
      assert.ok(fs.existsSync(fontPath), `Font file ${fontName} must exist`);

      const stat = fs.statSync(fontPath);
      assert.ok(stat.size > 10000, `Font file ${fontName} must be non-empty (size: ${stat.size})`);

      const buffer = fs.readFileSync(fontPath);
      assert.ok(
        buffer.subarray(0, 4).equals(woff2Magic),
        `Font file ${fontName} must start with wOF2 magic header`
      );
    }
  });

  test('view/html/mainHead.php uses Roboto, sets --tblr-font-sans-serif, and removes FA / Inter settings', () => {
    const headPath = path.join(rootDir, 'view/html/mainHead.php');
    const content = fs.readFileSync(headPath, 'utf8');

    assert.ok(content.includes('public/lib/roboto/roboto.css'), 'mainHead.php must reference roboto.css');
    assert.ok(!content.includes('public/lib/inter/inter.css'), 'mainHead.php must not reference inter.css');
    assert.ok(!content.includes('public/lib/fontawesome/css/all.min.css'), 'mainHead.php must not reference fontawesome');
    assert.ok(!content.includes('"cv03"'), 'mainHead.php must not include Inter-specific font-feature-settings');

    assert.match(
      content,
      /--tblr-font-sans-serif:\s*'Roboto',\s*-apple-system,\s*BlinkMacSystemFont,\s*San Francisco,\s*Segoe UI,\s*Helvetica Neue,\s*sans-serif;/
    );
  });

  test('index.php uses Roboto stylesheet and defines --tblr-font-sans-serif', () => {
    const indexPath = path.join(rootDir, 'index.php');
    const content = fs.readFileSync(indexPath, 'utf8');

    assert.ok(content.includes('public/lib/roboto/roboto.css'), 'index.php must reference roboto.css');
    assert.ok(!content.includes('public/lib/inter/inter.css'), 'index.php must not reference inter.css');
    assert.match(
      content,
      /--tblr-font-sans-serif:\s*'Roboto',\s*-apple-system,\s*BlinkMacSystemFont,\s*San Francisco,\s*Segoe UI,\s*Helvetica Neue,\s*sans-serif;/
    );
  });

  test('view/404/index.php uses Roboto stylesheet, defines --tblr-font-sans-serif, and removes Inter settings', () => {
    const notFoundPath = path.join(rootDir, 'view/404/index.php');
    const content = fs.readFileSync(notFoundPath, 'utf8');

    assert.ok(content.includes('public/lib/roboto/roboto.css'), '404 index.php must reference roboto.css');
    assert.ok(!content.includes('public/lib/inter/inter.css'), '404 index.php must not reference inter.css');
    assert.ok(!content.includes('"cv03"'), '404 index.php must not include Inter-specific font-feature-settings');
    assert.match(
      content,
      /--tblr-font-sans-serif:\s*'Roboto',\s*-apple-system,\s*BlinkMacSystemFont,\s*San Francisco,\s*Segoe UI,\s*Helvetica Neue,\s*sans-serif;/
    );
  });

  test('public/lib/fontawesome directory is deleted', () => {
    const faDir = path.join(rootDir, 'public/lib/fontawesome');
    assert.ok(!fs.existsSync(faDir), 'fontawesome directory should not exist');
  });
});

const { test, describe } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync, readdirSync, statSync } = require('node:fs');
const { join } = require('node:path');

describe('APP_NAME Browser Tab Title Standardization', () => {
  test('view/html/mainHead.php defines centralized dynamic APP_NAME title tag', () => {
    const content = readFileSync('view/html/mainHead.php', 'utf8');
    assert.match(
      content,
      /<title><\?php echo htmlspecialchars\(Conectar::getEnv\('APP_NAME', 'SIGODT'\)\); \?><\/title>/
    );
  });

  test('index.php loads APP_NAME and uses dynamic title tag', () => {
    const content = readFileSync('index.php', 'utf8');
    assert.match(
      content,
      /\$appName\s*=\s*Conectar::getEnv\('APP_NAME',\s*'SIGODT'\);/
    );
    assert.match(
      content,
      /<title><\?php echo htmlspecialchars\(\$appName\); \?><\/title>/
    );
  });

  test('view/404/index.php requires conexion, loads APP_NAME, and uses dynamic title tag', () => {
    const content = readFileSync('view/404/index.php', 'utf8');
    assert.match(
      content,
      /require_once\s+["']\.\.\/\.\.\/config\/conexion\.php["'];/
    );
    assert.match(
      content,
      /\$appName\s*=\s*Conectar::getEnv\('APP_NAME',\s*'SIGODT'\);/
    );
    assert.match(
      content,
      /<title><\?php echo htmlspecialchars\(\$appName\); \?><\/title>/
    );
  });

  test('all 17 view/*/index.php pages (excluding 404) do not define individual title tags', () => {
    const viewDirs = readdirSync('view').filter((dir) => {
      const fullPath = join('view', dir);
      return statSync(fullPath).isDirectory() && dir !== '404' && dir !== 'html';
    });

    const expectedViews = [
      'view/admin_giros_ciudadano/index.php',
      'view/consultar_documento/index.php',
      'view/consultar_mes/index.php',
      'view/consultar_nombre/index.php',
      'view/consultar_og/index.php',
      'view/dashboard/index.php',
      'view/giros_ciudadano/index.php',
      'view/inicio/index.php',
      'view/mnt_ciudadano/index.php',
      'view/mnt_empresa/index.php',
      'view/mnt_permisos/index.php',
      'view/mnt_procedimiento/index.php',
      'view/mnt_tasas/index.php',
      'view/mnt_tributos/index.php',
      'view/mnt_tupa/index.php',
      'view/reporte_usuario/index.php',
      'view/setting/index.php'
    ];

    assert.strictEqual(
      viewDirs.length,
      17,
      `Expected 17 view directories but found ${viewDirs.length}`
    );

    for (const viewFile of expectedViews) {
      const content = readFileSync(viewFile, 'utf8');
      assert.doesNotMatch(
        content,
        /<title>/i,
        `${viewFile} should not contain <title> tag`
      );
      assert.doesNotMatch(
        content,
        /<\/title>/i,
        `${viewFile} should not contain </title> tag`
      );
    }
  });
});

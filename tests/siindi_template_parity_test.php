<?php
// Artifact contract: this pinned SIINDI template must remain byte-identical.
// This check never loads application code, configuration, databases or providers.
$expected = [
    'seguridad/2fa/README.md' => '41959a8b3702618e06d7932a65f2f25a2df3b4ec00e71ea446fc249313b1ae12',
    'seguridad/2fa/README_ESTRUCTURA.md' => '3071591b2a8491d6ed5cff145a1baee9ac8c9f61d4ad331494f56702d7b325a6',
    'seguridad/2fa/assets/css/two_factor.css' => 'be4c55326257eb43140db2318b206b6900d3f732202469c7ae3a73c219663099',
    'seguridad/2fa/assets/js/qrcode.min.js' => 'c541ef06327885a8415bca8df6071e14189b4855336def4f36db54bde8484f36',
    'seguridad/2fa/assets/js/two_factor.js' => '869c14a43be882d7aaf106970e3a42f17f218546b5dc0d2e3e0494cf5041cdd6',
    'seguridad/2fa/controllers/TwoFactorController.php' => 'df02477dc0a9eb0f092732da7cb49d2b1aa14ced36d28871a1dd177b3e640eda',
    'seguridad/2fa/models/TwoFactorModel.php' => '684676c69d18a2a0219eaabdc6994325e70218bf918e1ddebb990cdc8c1c343d',
    'seguridad/2fa/services/SigurMailer.php' => 'f4031502e7d5d6a6ca94b9aef4050e46edaf02adf2ffb05214fee9879a97160d',
    'seguridad/2fa/services/SmtpMailer.php' => '253e2f8ab1e2c0439c2e6e39314589e5c1a154173ff8e70618114b608cd9a02d',
    'seguridad/2fa/services/TwoFactorService.php' => 'a5cc35c94d1b2e248de5dc223abd1b56a16193d4f9360e4dbf594df7125e9334',
    'seguridad/2fa/views/desafio_login.php' => 'ede6cc02207d17e975fc89e03c8f66724105810b0930072d19a217afc30f14bb',
    'seguridad/2fa/views/enrolar_login.php' => '88a7328ac36b09cf2fe13f21e83b5b6bf8ca03a875cb0d1866c4ea64fb32d42e',
    'seguridad/2fa/views/modal_enrolamiento.php' => '78f7ecf5f13e8b59b6723b0ad41117d1d594b1fb67db890b377e5a2ddc0dec2c',
    'seguridad/2fa/views/perfil_2fa.php' => '1d17b73dbac7152b00064ba6f8390b02d95e2ede81e16ad2a8f6a6fe8c516ea3',
    'seguridad/2fa/views/recuperar_acceso.php' => '09243e27dbf607ea2309787891b430d4ab1a04099c8be1b84258bab7cb0c96e8',
    'seguridad/2fa/views/selector_metodo.php' => '823856f973fc1152138c52766800dad242c0538ba3aba4e7ade51f95b52a11a7',
    'seguridad/actualizar_contrasena/views/index.php' => '5beb844e3a1b2c63d9b4c9b92cc09ccd57cfd0630b8f5b987a002ad2cf606ff3',
    'seguridad/primer_acceso/views/index.php' => '2576b0e0a963108e1e9892b59b5a29f338963f2ccd2dd332e2448387f955e36f',
    'seguridad/recuperar_contrasena/views/index.php' => 'a8b670f5174cc102fb0fea3be432bd1445c44c46cc6f63ce1781a7516c142219'
];
$root = dirname(__DIR__);
$failed = 0;
foreach ($expected as $path => $sha) {
    if (!is_file($root . '/' . $path) || hash_file('sha256', $root . '/' . $path) !== $sha) {
        fwrite(STDERR, "MISMATCH: $path\n");
        $failed++;
    }
}
if ($failed) {
    fwrite(STDERR, "FAIL: $failed template files differ\n");
    exit(1);
}
echo 'PASS: ' . count($expected) . " original SIINDI template files\n";

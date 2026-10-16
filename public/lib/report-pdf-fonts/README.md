# Local report PDF assets

These assets are used only by `view/reporte_usuario/`. No package installation, runtime CDN or remote font request is needed.

| Asset | Pin | License |
|---|---|---|
| jsPDF UMD minified browser distribution | npm `jspdf@4.2.1` | MIT, `../jspdf/LICENSE` |
| AutoTable minified browser plugin | npm `jspdf-autotable@5.0.9` | MIT, `../jspdf-autotable/LICENSE.txt` |
| PT Sans regular TTF, unmodified | Google Fonts commit `8b0a1d0f5983c89bc2b93f1b5fb55f9e252744b5` | SIL OFL 1.1, `OFL.txt` |

## Provenance and update boundary

`provenance.json` records each exact source URL, archive member where applicable, pin, license and file SHA256. Official npm registry version metadata confirmed both published releases; downloaded tarballs were checked against registry SHA512 integrity before extracting only distributions and licenses. AutoTable's published peer range is `^2 || ^3 || ^4`.

For an intentional update, obtain the pinned official npm release tarball or pinned Google Fonts source, verify integrity, inspect source/license metadata, replace only the listed assets and update the manifest. Do not run lifecycle scripts or add package/cache directories. Run report PHP/Node tests and inspect an extracted/rendered multipage synthetic PDF, including Spanish names.

The UMD bundle contains optional upstream APIs; this feature invokes only text, vector tables and `save`. It never invokes HTML conversion, remote images or PDF viewers. Existing unrelated site libraries are outside this update boundary. Hash verification establishes artifact identity, not a security audit or guarantee of publisher identity.

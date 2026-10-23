"""Build a fresh-install shared-hosting ZIP. Never includes local data or secrets."""
import argparse
import pathlib
import subprocess
import zipfile

ROOT = pathlib.Path(__file__).resolve().parent.parent


def build(destination):
    tracked = subprocess.check_output(
        ['git', 'ls-files', '--cached', '--others', '--exclude-standard', '-z'], cwd=ROOT
    ).decode('utf-8').split('\0')
    files = set()
    for name in tracked:
        path = pathlib.PurePosixPath(name)
        if not name or path.parts[0] not in {'public', 'protected', 'database', 'tools'} and name not in {
            '.htaccess', 'index.php', 'install.php', 'composer.json', 'composer.lock', 'INSTALL.md', 'README.md', 'LICENSE'
        }:
            continue
        if name.startswith(('protected/runtime/', 'public/uploads/')) and path.name not in {'.gitkeep', '.htaccess'}:
            continue
        if name.startswith('protected/config/') and path.name not in {'main.php'}:
            continue
        if name.startswith('tools/') and path.name not in {
            '.htaccess', 'bootstrap.php', 'upgrade.php', 'seed.php', 'seed-demo.php', 'seed-cms.php',
            'upgrade-cms.php', 'upgrade-shop.php', 'upgrade-albums.php', 'upgrade-catalogs-cache.php', 'upgrade-api.php',
            'reservations.php', 'notifications.php', 'backup.php', 'restore.php', 'upgrade-product-templates.php'
        }:
            continue
        if (ROOT / name).is_file():
            files.add(name)
    if not (ROOT / 'vendor/autoload.php').is_file():
        raise SystemExit('Run composer install --no-dev first; vendor is required in the release.')
    files.update(str(p.relative_to(ROOT)).replace('\\', '/') for p in (ROOT / 'vendor').rglob('*') if p.is_file())
    destination.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(destination, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=6) as archive:
        for name in sorted(files):
            archive.write(ROOT / name, name)
    print(f'{destination}\n{len(files)} files; {destination.stat().st_size / 1048576:.1f} MB')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=pathlib.Path, default=ROOT / 'protected/runtime/dist/bazaarche-shared-host.zip')
    build(parser.parse_args().output.resolve())

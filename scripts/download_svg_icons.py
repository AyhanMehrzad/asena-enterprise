#!/usr/bin/env python3
import os
import re
import subprocess
from concurrent.futures import ThreadPoolExecutor, as_completed

BASE_DIR = '/opt/lampp/htdocs/asena/asena-enterprise'
UI_ICONS_DIR = os.path.join(BASE_DIR, 'assets', 'icons', 'ui')
SPRITE_FILE = os.path.join(UI_ICONS_DIR, 'sprite.svg')

os.makedirs(UI_ICONS_DIR, exist_ok=True)

# 1. Discover all icons used across the project
pattern = re.compile(r'<span[^>]*class=[\"\'][^\"\']*material-symbols[^\'\"]*[\"\'][^>]*>\s*([a-zA-Z0-9_-]+)\s*</span>')
icons = set()
for root, dirs, files in os.walk(BASE_DIR):
    if any(p in root for p in ['.git', 'vendor', 'node_modules', 'uploads']):
        continue
    for f in files:
        if f.endswith(('.php', '.html', '.js')):
            try:
                content = open(os.path.join(root, f), encoding='utf-8', errors='ignore').read()
                for m in pattern.finditer(content):
                    icons.add(m.group(1).strip())
            except Exception:
                pass

# Extra common icons for error pages & offline UI
extra_icons = [
    'wifi_off', 'wifi', 'refresh', 'phone_in_talk', 'home', 'search', 'pets', 'arrow_back',
    'lock', 'security', 'shield', 'warning', 'error', 'info', 'check_circle', 'schedule',
    'speed', 'bolt', 'local_hospital', 'medical_services', 'inventory_2', 'shopping_cart',
    'cookie', 'sentiment_very_dissatisfied', 'autorenew', 'sports_esports', 'volume_up', 'volume_off'
]
icons.update(extra_icons)
icon_list = sorted(list(icons))
print(f"Total unique icons to download: {len(icon_list)}")

def download_icon(name):
    target_path = os.path.join(UI_ICONS_DIR, f"{name}.svg")
    if os.path.exists(target_path) and os.path.getsize(target_path) > 100:
        with open(target_path, 'r', encoding='utf-8') as f:
            content = f.read()
        if content.startswith('<svg'):
            return name, True, content

    urls = [
        f"https://raw.githubusercontent.com/google/material-design-icons/master/symbols/web/{name}/materialsymbolsoutlined/{name}_24px.svg",
        f"https://raw.githubusercontent.com/marella/material-symbols/main/svg/400/outlined/{name}.svg"
    ]

    for url in urls:
        try:
            res = subprocess.run(
                ['curl', '-s', '--max-time', '6', '--socks5-hostname', '127.0.0.1:10808', url],
                capture_output=True, text=True
            )
            data = res.stdout.strip()
            if data.startswith('<svg') and ('<path' in data or '<g' in data):
                with open(target_path, 'w', encoding='utf-8') as f:
                    f.write(data)
                return name, True, data
        except Exception:
            pass

    # Generic fallback icon if not found
    fallback_svg = '<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24"><path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/></svg>'
    with open(target_path, 'w', encoding='utf-8') as f:
        f.write(fallback_svg)
    return name, False, fallback_svg

symbols = []
success_count = 0
fallback_count = 0

with ThreadPoolExecutor(max_workers=16) as executor:
    futures = {executor.submit(download_icon, name): name for name in icon_list}
    for future in as_completed(futures):
        name, ok, content = future.result()
        if ok:
            success_count += 1
        else:
            fallback_count += 1

        # Extract inner path / viewBox for sprite symbol
        vb_match = re.search(r'viewBox=[\"\']([^\"\']+)[\"\']', content)
        viewbox = vb_match.group(1) if vb_match else "0 -960 960 960"
        
        # Extract inner contents
        inner = re.sub(r'^<svg[^>]*>', '', content, flags=re.IGNORECASE)
        inner = re.sub(r'</svg>$', '', inner, flags=re.IGNORECASE).strip()
        
        symbols.append(f'  <symbol id="icon-{name}" viewBox="{viewbox}">\n    {inner}\n  </symbol>')

# Assemble sprite.svg
sprite_content = '<?xml version="1.0" encoding="utf-8"?>\n<svg xmlns="http://www.w3.org/2000/svg" style="display: none;">\n'
sprite_content += '\n'.join(sorted(symbols))
sprite_content += '\n</svg>\n'

with open(SPRITE_FILE, 'w', encoding='utf-8') as f:
    f.write(sprite_content)

print(f"Downloaded: {success_count} real SVGs, {fallback_count} fallbacks.")
print(f"Unified Sprite generated at {SPRITE_FILE} (size: {os.path.getsize(SPRITE_FILE)} bytes).")

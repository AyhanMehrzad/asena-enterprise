import asyncio
import json
import urllib.request
import subprocess
import base64
import websockets

async def capture_viewport(width, height, output_path):
    chrome_proc = subprocess.Popen([
        '/usr/bin/google-chrome',
        '--headless=new',
        '--remote-debugging-port=9222',
        '--disable-gpu',
        '--no-sandbox',
        f'--window-size={width},{height}'
    ])
    await asyncio.sleep(1.5)

    try:
        ver = json.loads(urllib.request.urlopen('http://127.0.0.1:9222/json/version').read().decode('utf-8'))
        ws_url = ver['webSocketDebuggerUrl']

        async with websockets.connect(ws_url) as ws:
            msg_id = 1
            async def send_cmd(method, params=None):
                nonlocal msg_id
                cmd = {'id': msg_id, 'method': method}
                if params:
                    cmd['params'] = params
                msg_id += 1
                await ws.send(json.dumps(cmd))
                while True:
                    resp = json.loads(await ws.recv())
                    if resp.get('id') == cmd['id']:
                        return resp.get('result', {})

            target_res = await send_cmd('Target.createTarget', {'url': 'http://127.0.0.1:8085/site.php?slug=dr-alavi'})
            target_id = target_res['targetId']
            
            attach_res = await send_cmd('Target.attachToTarget', {'targetId': target_id, 'flatten': True})
            session_id = attach_res['sessionId']

            async def send_target_cmd(method, params=None):
                nonlocal msg_id
                cmd = {'id': msg_id, 'sessionId': session_id, 'method': method}
                if params:
                    cmd['params'] = params
                msg_id += 1
                await ws.send(json.dumps(cmd))
                while True:
                    resp = json.loads(await ws.recv())
                    if resp.get('id') == cmd['id']:
                        return resp.get('result', {})

            await send_target_cmd('Page.enable')
            await send_target_cmd('Emulation.setDeviceMetricsOverride', {
                'width': width,
                'height': height,
                'deviceScaleFactor': 1,
                'mobile': (width < 768)
            })
            await asyncio.sleep(2)

            # Capture header clip (y: 0 to 220)
            shot = await send_target_cmd('Page.captureScreenshot', {
                'format': 'png',
                'clip': {'x': 0, 'y': 0, 'width': width, 'height': 220, 'scale': 1}
            })
            with open(output_path, 'wb') as f:
                f.write(base64.b64decode(shot['data']))
            print(f"Captured {output_path} ({width}x{height})")

            await send_cmd('Target.closeTarget', {'targetId': target_id})
    finally:
        chrome_proc.terminate()

async def main():
    await capture_viewport(1440, 900, '/home/rupper/.gemini/antigravity-ide/brain/90c058ff-dca0-4e48-b837-01ab8db0844d/preview_header_desktop_1440.png')
    await capture_viewport(1280, 800, '/home/rupper/.gemini/antigravity-ide/brain/90c058ff-dca0-4e48-b837-01ab8db0844d/preview_header_desktop_1280.png')
    await capture_viewport(1024, 768, '/home/rupper/.gemini/antigravity-ide/brain/90c058ff-dca0-4e48-b837-01ab8db0844d/preview_header_tablet_1024.png')
    await capture_viewport(390, 844, '/home/rupper/.gemini/antigravity-ide/brain/90c058ff-dca0-4e48-b837-01ab8db0844d/preview_header_mobile_390.png')

if __name__ == '__main__':
    asyncio.run(main())

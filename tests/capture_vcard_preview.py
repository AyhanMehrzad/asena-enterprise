import asyncio
import json
import urllib.request
import subprocess
import time
import base64
import websockets

async def main():
    chrome_proc = subprocess.Popen([
        '/usr/bin/google-chrome',
        '--headless=new',
        '--remote-debugging-port=9222',
        '--disable-gpu',
        '--no-sandbox',
        '--window-size=1280,950'
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
            await send_target_cmd('DOM.enable')
            await asyncio.sleep(2)

            # 1. Scroll to asena-services section and take screenshot of Card 6
            await send_target_cmd('Runtime.evaluate', {
                'expression': 'document.getElementById("asena-services")?.scrollIntoView({behavior: "instant", block: "center"});'
            })
            await asyncio.sleep(1)

            card_shot = await send_target_cmd('Page.captureScreenshot', {'format': 'png'})
            with open('/home/rupper/.gemini/antigravity-ide/brain/90c058ff-dca0-4e48-b837-01ab8db0844d/preview_vcard_card_grid.png', 'wb') as f:
                f.write(base64.b64decode(card_shot['data']))
            print("Captured preview_vcard_card_grid.png")

            # 2. Click to open VCard Modal
            await send_target_cmd('Runtime.evaluate', {
                'expression': 'openVCardModal();'
            })
            await asyncio.sleep(1)

            modal_shot = await send_target_cmd('Page.captureScreenshot', {'format': 'png'})
            with open('/home/rupper/.gemini/antigravity-ide/brain/90c058ff-dca0-4e48-b837-01ab8db0844d/preview_vcard_modal_active.png', 'wb') as f:
                f.write(base64.b64decode(modal_shot['data']))
            print("Captured preview_vcard_modal_active.png")

            # 3. Switch to website QR tab
            await send_target_cmd('Runtime.evaluate', {
                'expression': 'switchQrTab("website");'
            })
            await asyncio.sleep(0.5)

            website_qr_shot = await send_target_cmd('Page.captureScreenshot', {'format': 'png'})
            with open('/home/rupper/.gemini/antigravity-ide/brain/90c058ff-dca0-4e48-b837-01ab8db0844d/preview_vcard_modal_website_tab.png', 'wb') as f:
                f.write(base64.b64decode(website_qr_shot['data']))
            print("Captured preview_vcard_modal_website_tab.png")

            await send_cmd('Target.closeTarget', {'targetId': target_id})
    finally:
        chrome_proc.terminate()

if __name__ == '__main__':
    asyncio.run(main())

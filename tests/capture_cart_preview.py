import asyncio
import json
import urllib.request
import subprocess
import time
import base64
import websockets

async def main():
    # 1. Start Chrome
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
        # 2. Get WebSocket debugger URL
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

            # Create target page
            target_res = await send_cmd('Target.createTarget', {'url': 'http://127.0.0.1:8085/site.php?slug=razi-hospital'})
            target_id = target_res['targetId']
            
            # Attach to target
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
            await send_target_cmd('Runtime.enable')

            # Wait for load
            await asyncio.sleep(2.0)

            # 3. Scroll to storefront
            await send_target_cmd('Runtime.evaluate', {
                'expression': 'document.getElementById("storefront")?.scrollIntoView({ behavior: "instant", block: "center" })'
            })
            await asyncio.sleep(0.5)

            # Capture storefront screenshot
            shot1 = await send_target_cmd('Page.captureScreenshot', {'format': 'png'})
            with open('/home/rupper/.gemini/antigravity-ide/brain/90c058ff-dca0-4e48-b837-01ab8db0844d/preview_tenant_storefront_cards.png', 'wb') as f:
                f.write(base64.b64decode(shot1['data']))
            print("Captured: preview_tenant_storefront_cards.png")

            # 4. Click instant buy on first item
            await send_target_cmd('Runtime.evaluate', {
                'expression': 'document.querySelector("#storefront button[onclick*=\'addItem(this, true)\']")?.click()'
            })
            await asyncio.sleep(1.0)

            # Capture cart drawer screenshot
            shot2 = await send_target_cmd('Page.captureScreenshot', {'format': 'png'})
            with open('/home/rupper/.gemini/antigravity-ide/brain/90c058ff-dca0-4e48-b837-01ab8db0844d/preview_tenant_cart_drawer.png', 'wb') as f:
                f.write(base64.b64decode(shot2['data']))
            print("Captured: preview_tenant_cart_drawer.png")

            # 5. Click checkout step button
            await send_target_cmd('Runtime.evaluate', {
                'expression': 'tenantCart.goToCheckoutStep()'
            })
            await asyncio.sleep(0.5)

            # Capture checkout form screenshot
            shot3 = await send_target_cmd('Page.captureScreenshot', {'format': 'png'})
            with open('/home/rupper/.gemini/antigravity-ide/brain/90c058ff-dca0-4e48-b837-01ab8db0844d/preview_tenant_checkout_form.png', 'wb') as f:
                f.write(base64.b64decode(shot3['data']))
            print("Captured: preview_tenant_checkout_form.png")

    finally:
        chrome_proc.terminate()
        print("Chrome finished.")

if __name__ == '__main__':
    asyncio.run(main())

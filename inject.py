import re

b64 = open('WhatWeDo/base64_logo.txt').read()
js = open('WhatWeDo/robot.js', encoding='utf-8').read()

pattern = re.compile(r'    // Hack to bypass local file CORS: Steal the logo from the navbar which the browser already downloaded!.*?    } else {\n        // Fallback if navbar logo is missing\n        logoTexture = new THREE\.TextureLoader\(\)\.load\(\'images/rz-logo\.png\'\);\n    }', re.DOTALL)

new_block = f'''    // Bypass local file CORS strictly by using an embedded base64 string of the logo
    const rlabzBase64 = "{b64}";
    const logoTexture = new THREE.TextureLoader().load(rlabzBase64, function(texture) {{
        if (THREE.SRGBColorSpace) {{
            texture.colorSpace = THREE.SRGBColorSpace;
        }} else if (THREE.sRGBEncoding) {{
            texture.encoding = THREE.sRGBEncoding;
        }}
    }});'''

if pattern.search(js):
    js = pattern.sub(new_block, js)
    open('WhatWeDo/robot.js', 'w', encoding='utf-8').write(js)
    print('Successfully injected!')
else:
    print('Could not find block to replace!')

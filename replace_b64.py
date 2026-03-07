import re

new_b64 = open('WhatWeDo/base64_logo.txt').read()
js = open('WhatWeDo/robot.js', encoding='utf-8').read()

# Replace the old base64 string (it starts with data:image/png;base64,)
pattern = re.compile(r'const rlabzBase64 = "data:image/png;base64,[^"]+";')
new_line = f'const rlabzBase64 = "{new_b64}";'
match = pattern.search(js)
if match:
    js = pattern.sub(new_line, js)
    # Also remove any repeat/offset since we now use the full image
    js = js.replace(
        '''        if (THREE.SRGBColorSpace) {
            texture.colorSpace = THREE.SRGBColorSpace;
        } else if (THREE.sRGBEncoding) {
            texture.encoding = THREE.sRGBEncoding;
        }
    });''',
        '''        if (THREE.SRGBColorSpace) {
            texture.colorSpace = THREE.SRGBColorSpace;
        } else if (THREE.sRGBEncoding) {
            texture.encoding = THREE.sRGBEncoding;
        }
        console.log("ROBOT INFO: Base64 logo loaded!");
    });'''
    )
    open('WhatWeDo/robot.js', 'w', encoding='utf-8').write(js)
    print('Success! base64 replaced.')
else:
    print('ERROR: Could not find old base64 block!')

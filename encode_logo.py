import base64, io
from PIL import Image

# Load image, resize to manageable size, preserve transparency
img = Image.open('WhatWeDo/images/rz-logo.png').convert('RGBA')
img.thumbnail((256, 180))  # Keep proportional

buf = io.BytesIO()
img.save(buf, format='PNG', optimize=True)
b64 = base64.b64encode(buf.getvalue()).decode('utf-8')
data_url = 'data:image/png;base64,' + b64
with open('WhatWeDo/base64_logo.txt', 'w') as f:
    f.write(data_url)
print(f'Done! Size: {len(data_url)} chars, Image: {img.size}')

import re

filepath = r'c:\Users\annjo\Desktop\rajagiri\Projects\rlabzPro\Rlabz\assets\js\robot.js'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# Pattern: from "// RLabz Logo on Chest Screen" through end of the closing comment block */ 
# ending just before "    // Neck"
pattern = r'    // RLabz Logo on Chest Screen.*?    \*/\r?\n'

replacement = '''    // HEART (Glowing Pulse)
    const chestLight = new THREE.Mesh(
        new THREE.CircleGeometry(0.15, 32),
        new THREE.MeshBasicMaterial({ color: 0x0ea5e9 }) // Cyan glow
    );
    chestLight.position.z = 0.51 + 0.05 / 2 + 0.01;
    chestLight.position.y = 0.1;
    bodyGroup.add(chestLight);\r\n'''

matches = re.findall(pattern, content, re.DOTALL)
print(f"Found {len(matches)} match(es)")
if matches:
    print(f"Match starts with: {matches[0][:80]!r}")
    print(f"Match ends with: {matches[0][-80:]!r}")

new_content = re.sub(pattern, replacement, content, count=1, flags=re.DOTALL)

if new_content == content:
    print("ERROR: No replacement was made!")
else:
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(new_content)
    print("SUCCESS: Logo removed, chest light restored.")

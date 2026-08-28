import re

path = r'c:\xampp\htdocs\CitiLife-System\views\layouts\partials\scripts.php'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

# For fetch with options
content = re.sub(
    r"fetch\((.*?),\s*\{",
    r"fetch(\1, { credentials: 'same-origin', ",
    content
)

# For fetch without options
content = re.sub(
    r"fetch\((.*?)\)(?!\s*\{)(?!\s*\.catch)",
    r"fetch(\1, { credentials: 'same-origin' })",
    content
)

# Fix double credentials if already existed (unlikely, but safe)
# We don't have to if it wasn't there.

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Replaced fetch calls")

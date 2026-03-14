import os
import re

directories = [
    'resources/views/alumni',
    'resources/views/tracking',
    'resources/views/settings',
]

replacements = {
    r'\bbg-white\b(?! dark:bg-)': 'bg-white dark:bg-gray-800',
    r'\bborder-gray-200\b(?! dark:border-)': 'border-gray-200 dark:border-gray-700',
    r'\bborder border-gray-100\b(?! dark:border-)': 'border border-gray-100 dark:border-gray-700',
    r'\bborder-b border-gray-100\b(?! dark:border-)': 'border-b border-gray-100 dark:border-gray-700',
    r'\bborder-gray-300\b(?! dark:border-)': 'border-gray-300 dark:border-gray-600',
    r'\bbg-gray-50\b(?! dark:bg-)': 'bg-gray-50 dark:bg-gray-900/50',
    r'\bbg-gray-100\b(?! dark:bg-)': 'bg-gray-100 dark:bg-gray-800',
    r'\btext-gray-900\b(?! dark:text-)': 'text-gray-900 dark:text-white',
    r'\btext-gray-800\b(?! dark:text-)': 'text-gray-800 dark:text-gray-100',
    r'\btext-gray-700\b(?! dark:text-)': 'text-gray-700 dark:text-gray-200',
    r'\btext-gray-600\b(?! dark:text-)': 'text-gray-600 dark:text-gray-300',
    r'\btext-gray-500\b(?! dark:text-)': 'text-gray-500 dark:text-gray-400',
    r'\btext-gray-400\b(?! dark:text-)': 'text-gray-400 dark:text-gray-500',
    r'\bdivide-gray-100\b(?! dark:divide-)': 'divide-gray-100 dark:divide-gray-700',
    r'\bdivide-gray-200\b(?! dark:divide-)': 'divide-gray-200 dark:divide-gray-700',
    r'\bhover:bg-gray-50\b(?! dark:hover:)': 'hover:bg-gray-50 dark:hover:bg-gray-700',
    r'\bhover:bg-gray-100\b(?! dark:hover:)': 'hover:bg-gray-100 dark:hover:bg-gray-700',
}

base_dir = r"d:\Apps\Laragon\tracking-alumni"

for directory in directories:
    dir_path = os.path.join(base_dir, directory)
    if not os.path.exists(dir_path):
        continue
    for root, _, files in os.walk(dir_path):
        for file in files:
            if file.endswith('.blade.php'):
                file_path = os.path.join(root, file)
                with open(file_path, 'r', encoding='utf-8') as f:
                    content = f.read()
                
                new_content = content
                for pattern, repl in replacements.items():
                    # Only replace inside class="..."
                    # A better way is to substitute directly since Tailwind classes are unique enough
                    # But to be safe, we can just do global regex replace for these specific tokens
                    new_content = re.sub(pattern, repl, new_content)
                
                if new_content != content:
                    with open(file_path, 'w', encoding='utf-8') as f:
                        f.write(new_content)
                    print(f"Updated {file_path}")

print("Done")

import os
import shutil
import zipfile

if os.path.isfile('wp-admin-notes.zip'):
    os.remove('wp-admin-notes.zip')

dist = 'dist'

if not os.path.isdir(dist):
    os.mkdir(dist)
else:
    shutil.rmtree(dist)
    os.mkdir(dist)

# copying root files
shutil.copy('wp-admin-notes.php', 'dist/wp-admin-notes.php')
shutil.copy('README.md', 'dist/README.md')
shutil.copy('LICENSE', 'dist/LICENSE')

# assets setup
os.mkdir(os.path.join(dist, 'assets'))
os.mkdir(os.path.join(dist, 'assets', 'css'))
os.mkdir(os.path.join(dist, 'assets', 'js'))

# copying assets files
shutil.copy('assets/css/mdn-admin-notes.min.css', 'dist/assets/css/mdn-admin-notes.min.css')
shutil.copy('assets/js/mdn-admin-notes.min.js', 'dist/assets/js/mdn-admin-notes.min.js')
shutil.copy('assets/js/mdn-admin-skip-release.min.js', 'dist/assets/js/mdn-admin-skip-release.min.js')

# inc setup
os.mkdir(os.path.join(dist, 'inc'))
shutil.copy('inc/mdn-ajax-calls.inc.php', 'dist/inc/mdn-ajax-calls.inc.php')

# languages setup
shutil.copytree('languages', 'dist/languages')

# vendor setup
shutil.copytree('vendor', 'dist/vendor')


# zipping it up
with zipfile.ZipFile('wp-admin-notes.zip', 'w', zipfile.ZIP_DEFLATED) as zipf:
    for root, dirs, files in os.walk('dist'):
        for file in files:
            file_path = os.path.join(root, file)
            target = '\\'.join(file_path.split('\\')[1:])
            zipf.write(file_path, target)


# cleaning up
shutil.rmtree('dist')
const fs = require('fs').promises;
const path = require('path');
const runner = require('child_process');

// Read package information dynamically from package.json
const packageJson = require('./package.json');
const version = packageJson.version;
const phpversion = packageJson.phpversion;
const modxversion = packageJson.modxversion;
const currentYear = new Date().getFullYear();
const startYear = parseInt(packageJson.startYear) || currentYear;
const yearRange = currentYear > startYear ? `${startYear}-${currentYear}` : `${startYear}`;
const dateStr = new Date().toISOString()
    .split('T')[0];

const copyrightRegex = new RegExp(`Copyright ${startYear}(-\\d{4})? by`, 'g');
const copyrightReplace = `Copyright ${yearRange} by`;
const apiRegex = new RegExp(`&copy; ${startYear}(-\\d{4})?`, 'g');
const apiReplace = `&copy; ${yearRange}`;
const banner = `/*!\n * ${packageJson.fullname} - ${packageJson.description}\n * Version: ${packageJson.version}\n * Build date: ${dateStr}\n */\n`;

let versionParts = version.split('-');
let versionNumber = versionParts[0];
let versionRelease = versionParts[1] || 'pl';
let versionFull = versionNumber + '-' + versionRelease;

// Helper: Replace string/regex in a file
async function replaceInFile(filePath, replacements, message = 'file') {
    try {
        let content = await fs.readFile(filePath, 'utf8');
        replacements.forEach(({ search, replace }) => {
            content = content.replace(search, replace);
        });
        await fs.writeFile(filePath, content, 'utf8');
        console.log(`✓ Updated ${message}: ${filePath}`);
    } catch (e) {
        console.warn(`⚠ Error updating ${filePath}`);
    }
}

async function taskBump() {
    console.log(`Bump (with version ${versionFull} and daterange: ${yearRange})...`);
    const copyrightFiles = [
        'core/components/commerce_cursus/src/Commerce_Cursus.php',
    ];
    for (const file of copyrightFiles) {
        await replaceInFile(file, [{
            search: copyrightRegex,
            replace: copyrightReplace
        }], 'daterange in');
    }
    await replaceInFile('core/components/commerce_cursus/src/Commerce_Cursus.php', [{
        search: /version = '\d+\.\d+\.\d+-?[0-9a-z]*'/ig,
        replace: `version = '${version}'`
    }], 'version in');
    // const docFiles = [
    //     'zensical.toml',
    // ];
    // for (const file of docFiles) {
    //    await replaceInFile(file, [{
    //         search: apiRegex,
    //         replace: apiReplace
    //     }], 'daterange in');
    // }
    // await replaceInFile('docs/index.md', [{
    //     search: /[*-] MODX Revolution \d.\d.*/g,
    //     replace: '* MODX Revolution ' + modxversion + '+'
    // }, {
    //     search: /[*-] PHP (v)?\d.\d.*/g,
    //     replace: '* PHP ' + phpversion + '+'
    // }], 'requirements in');
    await replaceInFile('core/components/commerce_cursus/composer.json', [{
        search: /"php": "\d.\d.*?"/g,
        replace: '"php": "' + phpversion + '"'
    }, {
        search: /"php": ">=\d.\d.*?"/g,
        replace: '"php": ">=' + phpversion + '"'
    }], 'requirements in');
    await replaceInFile('_build/build.transport.php', [{
        search: /'PKG_VERSION', '\d+\.\d+\.\d+-?[0-9a-z]*'/ig,
        replace: `'PKG_VERSION', '${versionNumber}'`
    }, {
        search: /'PKG_RELEASE', '.*?'/ig,
        replace: `'PKG_RELEASE', '${versionRelease}'`
    }], 'version in');
    await replaceInFile('_build/build.transport.php', [{
        search: /'php' => '>=\d+(\.\d+){1,2}'/ig,
        replace: `'php' => '>=${phpversion}'`
    }, {
        search: /'modx' => '>=\d+(\.\d+){1,2}'/ig,
        replace: `'modx' => '>=${modxversion}'`
    }], 'requirements in');
    const buildFiles = [
        '_build/version.json',
        'core/components/commerce_cursus/composer.json',
    ];
    for (const file of buildFiles) {
        await replaceInFile(file, [{
            search: /"version": "\d+\.\d+\.\d+-?[0-9a-z]*"/ig,
            replace: `"version": "${version}"`
        }], 'version in');
    }
}

async function taskTransport() {
    console.log('Creating transport package...');
    const phpScriptPath = path.resolve(__dirname, '_build/build.transport.php');
    runner.exec('php ' + phpScriptPath, function (err, phpResponse, stderr) {
        if (err) {
            console.log(err);
        }
        console.log(phpResponse);
    });
}

const action = process.argv[2];
if (action === 'bump') {
    taskBump();
} else if (action === 'transport') {
    taskTransport();
} else {
    // Default: Beides ausführen
    taskBump()
        .then(() => taskTransport());
}

console.log('Done!');

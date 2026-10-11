const fs = require('fs');

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
function replaceInFile(filePath, replacements, message = 'file') {
    if (!fs.existsSync(filePath)) {
        console.warn(`⚠ File not found: ${filePath}`);
        return;
    }
    let content = String(fs.readFileSync(filePath, 'utf8'));
    replacements.forEach(({ search, replace }) => {
        content = content.replace(search, replace);
    });
    fs.writeFileSync(filePath, content, 'utf8');
    console.log(`✓ Updated ${message}: ${filePath}`);
}

async function taskBump() {
    console.log(`Bump (with version ${versionFull} and daterange: ${yearRange})...`);
    const copyrightFiles = [
        'core/components/commerce_cursus/src/Commerce_Cursus.php',
    ];
    copyrightFiles.forEach(file => {
        replaceInFile(file, [{
            search: copyrightRegex,
            replace: copyrightReplace
        }], 'daterange in');
    });
    replaceInFile('core/components/commerce_cursus/src/Commerce_Cursus.php', [{
        search: /version = '\d+\.\d+\.\d+-?[0-9a-z]*'/ig,
        replace: `version = '${version}'`
    }], 'version in');
    // const docFiles = [
    //     'zensical.toml',
    // ];
    // docFiles.forEach(file => {
    //     replaceInFile(file, [{
    //         search: apiRegex,
    //         replace: apiReplace
    //     }], 'daterange in');
    // });
    // replaceInFile('docs/index.md', [{
    //     search: /[*-] MODX Revolution \d.\d.*/g,
    //     replace: '* MODX Revolution ' + modxversion + '+'
    // }, {
    //     search: /[*-] PHP (v)?\d.\d.*/g,
    //     replace: '* PHP ' + phpversion + '+'
    // }], 'requirements in');
    replaceInFile('core/components/commerce_cursus/composer.json', [{
        search: /"php": "\d.\d.*?"/g,
        replace: '"php": "' + phpversion + '"'
    }, {
        search: /"php": ">=\d.\d.*?"/g,
        replace: '"php": ">=' + phpversion + '"'
    }], 'requirements in');
    replaceInFile('_build/build.transport.php', [{
        search: /PKG_VERSION = '\d+\.\d+\.\d+-?[0-9a-z]*'/ig,
        replace: `PKG_VERSION = '${versionNumber}'`
    }, {
        search: /PKG_RELEASE = '.*?'/ig,
        replace: `PKG_RELEASE = '${versionRelease}'`
    }], 'version in');
    replaceInFile('_build/build.transport.php', [{
        search: /'php' => '>=\d+(\.\d+){1,2}'/ig,
        replace: `'php' => '>=${phpversion}'`
    }, {
        search: /'modx' => '>=\d+(\.\d+){1,2}'/ig,
        replace: `'modx' => '>=${modxversion}'`
    }], 'requirements in');
    const buildFiles = [
        '_build/config.json',
        'core/components/commerce_cursus/composer.json',
    ];
    buildFiles.forEach(file => {
        replaceInFile(file, [{
            search: /"version": "\d+\.\d+\.\d+-?[0-9a-z]*"/ig,
            replace: `"version": "${version}"`
        }], 'version in');
    });
}

const action = process.argv[2];
if (action === 'bump') {
    taskBump();
} else {
    // Default: Beides ausführen
    taskBump();
}

console.log('Done!');

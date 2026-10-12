<?php

/**
 * @param string $filename The name of the file.
 * @return string The file's content
 * @by splittingred
 */
function getSnippetContent($filename = '')
{
    $o = file_get_contents($filename);
    $o = str_replace('<?php', '', $o);
    $o = str_replace('?>', '', $o);
    return trim($o);
}

$tstart = microtime(true);
set_time_limit(0);

if (!defined('MOREPROVIDER_BUILD')) {
    /* define version */
    define('PKG_NAME', 'Commerce_Cursus');
    define('PKG_NAMESPACE', 'commerce_cursus');
    define('PKG_VERSION', '1.2.1');
    define('PKG_RELEASE', 'pl2');

    /* load modx */
    require_once dirname(__DIR__) . '/config.core.php';
    require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
    $modx = new modX();
    $modx->initialize('mgr');
    $modx->setLogLevel(xPDO::LOG_LEVEL_INFO);
    $modx->setLogTarget();
    $targetDirectory = dirname(__DIR__) . '/_packages/';
} else {
    $targetDirectory = MOREPROVIDER_BUILD_TARGET;
}

$root = dirname(__DIR__) . '/';
$sources = [
    'root' => $root,
    'build' => $root . '_build/',
    'events' => $root . '_build/events/',
    'resolvers' => $root . '_build/resolvers/',
    'validators' => $root . '_build/validators/',
    'data' => $root . '_build/data/',
    'plugins' => $root . '_build/elements/plugins/',
    'snippets' => $root . '_build/elements/snippets/',
    'source_core' => $root . 'core/components/' . PKG_NAMESPACE,
    'source_assets' => $root . 'assets/components/' . PKG_NAMESPACE,
    'lexicon' => $root . 'core/components/' . PKG_NAMESPACE . '/lexicon/',
    'docs' => $root . 'core/components/' . PKG_NAMESPACE . '/docs/',
    'model' => $root . 'core/components/' . PKG_NAMESPACE . '/model/',
];

$modx->loadClass('transport.modPackageBuilder', '', false, true);
$builder = new modPackageBuilder($modx);
$builder->directory = $targetDirectory;
$builder->createPackage(PKG_NAMESPACE, PKG_VERSION, PKG_RELEASE);
$builder->registerNamespace(PKG_NAMESPACE, false, true, '{core_path}components/' . PKG_NAMESPACE . '/', '{assets_path}components/' . PKG_NAMESPACE . '/');

$modx->log(xPDO::LOG_LEVEL_INFO, 'Packaged in namespace.');
flush();

$builder->package->put(
    [
        'source' => $sources['source_core'],
        'target' => "return MODX_CORE_PATH . 'components/';",
    ],
    [
        xPDOTransport::ABORT_INSTALL_ON_VEHICLE_FAIL => true,
        'vehicle_class' => 'xPDOFileVehicle',
        'validate' => [
            [
                'type' => 'php',
                'source' => $sources['validators'] . 'requirements.script.php'
            ]
        ],
        'resolve' => [
            [
                'type' => 'php',
                'source' => $sources['resolvers'] . 'loadmodules.resolver.php',
            ],
            [
                'type' => 'php',
                'source' => $sources['resolvers'] . 'events.resolver.php',
            ]
        ]
    ]
);
$modx->log(xPDO::LOG_LEVEL_INFO, 'Packaged in core, requirements validator, and module loading resolver.');
flush();

/**
 * Settings
 */
$settings = include $sources['data'] . 'transport.settings.php';
if (is_array($settings)) {
    $attributes = [
        xPDOTransport::UNIQUE_KEY => 'key',
        xPDOTransport::PRESERVE_KEYS => true,
        xPDOTransport::UPDATE_OBJECT => false,
    ];
    foreach ($settings as $setting) {
        $vehicle = $builder->createVehicle($setting, $attributes);
        $builder->putVehicle($vehicle);
    }
    $modx->log(xPDO::LOG_LEVEL_INFO, 'Packaged in ' . count($settings) . ' system settings.');
    flush();
    unset($settings, $setting, $attributes);
}

/**
 * Category
 */
$category = $modx->newObject('modCategory');
$category->set('category', 'Cursus for Commerce');
$modx->log(xPDO::LOG_LEVEL_INFO, 'Created category.');
flush();

/**
 * Snippets
 */
$snippets = include $sources['data'] . 'transport.snippets.php';
if (is_array($snippets)) {
    $category->addMany($snippets);
    $modx->log(xPDO::LOG_LEVEL_INFO, 'Packaged in ' . count($snippets) . ' snippets.');
    flush();
}
unset($snippets);

/**
 * Plugins
 */
$plugins = include $sources['data'] . 'transport.plugins.php';
if (is_array($plugins)) {
    $category->addMany($plugins, 'Plugins');
    $modx->log(xPDO::LOG_LEVEL_INFO, 'Packaged in ' . count($plugins) . ' plugins.');
    flush();
}
unset($plugins);

$attr = [
    xPDOTransport::UNIQUE_KEY => 'category',
    xPDOTransport::PRESERVE_KEYS => false,
    xPDOTransport::UPDATE_OBJECT => true,
    xPDOTransport::RELATED_OBJECTS => true,
    xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
        'Snippets' => [
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',
        ],
        'Plugins' => [
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                'PluginEvents' => [
                    xPDOTransport::PRESERVE_KEYS => true,
                    xPDOTransport::UPDATE_OBJECT => false,
                    xPDOTransport::UNIQUE_KEY => ['pluginid', 'event'],
                ],
            ],
        ],
    ]
];

$vehicle = $builder->createVehicle($category, $attr);
$builder->putVehicle($vehicle);
$modx->log(xPDO::LOG_LEVEL_INFO, 'Packaged in category.');
flush();

/* now pack in the license file, readme and setup options */
$builder->setPackageAttributes([
    'license' => file_get_contents($sources['docs'] . 'license.txt'),
    'readme' => file_get_contents($sources['docs'] . 'readme.txt'),
    'changelog' => file_get_contents($sources['docs'] . 'changelog.txt'),
    'requires' => [
        'php' => '>=7.4',
        'modx' => '>=2.8',
    ]
]);
$modx->log(xPDO::LOG_LEVEL_INFO, 'Packaged in package attributes.');
flush();

$modx->log(xPDO::LOG_LEVEL_INFO, 'Packing...');
flush();
$builder->pack();
$modx->cacheManager->deleteTree($root . '_packages/' . $builder->getSignature(), [
    'deleteTop' => true,
    'extensions' => []
]);

$totalTime = microtime(true) - $tstart;
$totalTime = sprintf("%2.6f s", $totalTime);

$modx->log(xPDO::LOG_LEVEL_INFO, "\nPackage Built.\nExecution time: $totalTime\n");

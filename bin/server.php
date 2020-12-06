<?php

try {
    require __DIR__ . '/../../app/bootstrap.php';
    require __DIR__ . '/../src/AsyncHttp.php';
} catch (\Exception $e) {
    echo $e->getMessage();
    exit(1);
}

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
/** @var \AmGraphQl\App\AsyncHttp $app */
$app = $bootstrap->createApplication(\AmGraphQl\App\AsyncHttp::class);
$app->launch();

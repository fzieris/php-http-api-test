<?php

require_once __DIR__ . '/vendor/autoload.php';

$twig = new Twig_Environment(new Twig_Loader_Filesystem(__DIR__ . DIRECTORY_SEPARATOR . "templates"));

$testUrl = filter_input(INPUT_POST, 'url');

$results = [];
$exception = null;

if ($testUrl !== null && $testUrl !== "") {
    $client = new GuzzleHttp\Client();
    try {
        $testSuite = new TestSuite($testUrl, $client);
        $results = $testSuite->allTests();
    } catch (Exception $e) {
        $exception = $e;
    }
} else {
    $testUrl = "http://localhost:8080";
}

echo $twig->render('results.twig', ['url' => $testUrl, 'results' => $results, 'exception' => $exception]);

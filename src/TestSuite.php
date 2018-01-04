<?php

use Psr\Http\Message\ResponseInterface;

class TestSuite {
    private $url;
    private $client;

    public function __construct($url, $client) {
        $this->url = $url;
        $this->client = $client;
    }

    public function allTests() {
        return array_map(function($i) {
            return $this->{"test$i"}();
        }, range(1, 6));
    }

    public function test1() {
        $addrs = ['/', '/index.htm', '/hallo.htm'];

        $responses = array_map(function($addr) { return $this->getResponse($addr); }, $addrs);
        $bodies = array_map(function($response) { return $response->getBody()->getContents(); }, $responses);

        $result = new TestResult();

        $result->name = "/";

        $result->expected = join(array_map(function($addr) { return "<code>$addr</code>: Status 200<br>"; }, $addrs));
        $result->expected .= "Inhalte gleich und nicht leer";

        $last = array_reduce($bodies, function($carry, $item) {
            if ($carry === null || $carry === $item) {
                return $item;
            }
            return "";
        }, null);

        $result->actual = join(array_map(function($addr, $response, $body) {
            return "<code>$addr</code>: Status " .$response->getStatusCode() .
                "<pre>" . htmlspecialchars($body) . "</pre>";
        }, $addrs, $responses, $bodies));

        $result->actual .= "Inhalte: " . (($last !== "") ? "gleich" : "verschieden oder leer");

        $status = array_reduce($responses, function($carry, $item) {
            return $carry && ($item->getStatusCode() == 200);
        }, true);

        $result->resultClass = ($status && $last !== "") ? TestResult::SUCCESS : TestResult::FAILURE;

        return $result;
    }

    public function test2() {
        $index_html = $this->getResponse('/index.html');

        $result = new TestResult();

        $result->name = "/index.html";
        $result->expected = "Status 302<br>";
        $addr = "index.htm";
        $result->expected .= "<code>Location:</code> $addr<br>";

        $result->actual  = "Status " . $index_html->getStatusCode() . "<br>";
        $result->actual .= "<code>Location:</code> " . $index_html->getHeaderLine('Location') . "<br>";

        if ($index_html->getStatusCode() == 302 &&
            $index_html->getHeaderLine('Location') == $addr) {
            $result->resultClass = TestResult::SUCCESS;
        } else {
            $result->resultClass = TestResult::FAILURE;
        }

        return $result;
    }

    public function test3() {
        $super_secret_html = $this->getResponse('/super-secret.htm');

        $result = new TestResult();

        $result->name = "/super-secret.htm";
        $result->expected = "Status 403<br>";
        $result->actual  = "Status " . $super_secret_html->getStatusCode() .
            "<pre>" . htmlspecialchars($super_secret_html->getBody()) . "</pre>";

        if ($super_secret_html->getStatusCode() == 403) {
            $result->resultClass = TestResult::SUCCESS;
        } else {
            $result->resultClass = TestResult::FAILURE;
        }

        return $result;
    }

    public function test4() {
        $secret_html = $this->getResponse('/secret.htm');
        $secret_html_pass = $this->getResponse('/secret.htm', ['auth' => ['htw', 'webdev']]);

        $result = new TestResult();

        $result->name = "/secret.htm";
        $result->expected  = "Status ohne Auth: 401<br>";
        $result->expected .= "Status mit Auth: 200";

        $result->actual  = "Status ohne Auth: " . $secret_html->getStatusCode() . "<br>";
        $result->actual .= "Status mit Auth: " . $secret_html_pass->getStatusCode() .
            "<pre>" . htmlspecialchars($secret_html_pass->getBody()) . "</pre>";

        if ($secret_html->getStatusCode() == 401 && $secret_html_pass->getStatusCode() == 200) {
            $result->resultClass = TestResult::SUCCESS;
        } else {
            $result->resultClass = TestResult::FAILURE;
        }

        return $result;
    }

    public function test5() {
        $script = $this->getResponse('/zeit.htm');
        $body1 =  $script->getBody()->getContents();

        sleep(1);

        $script2 = $this->getResponse('/zeit.htm');
        $body2 = $script2->getBody()->getContents();

        $result = new TestResult();

        $result->name = "/zeit.htm";

        $result->expected  = "Status: 200<br>Verschiedene Zeiten";

        $result->actual  = "Status: " . $script->getStatusCode();
        $result->actual .= "<pre>" . htmlspecialchars($body1) . "</pre>";
        $result->actual .= "<pre>" . htmlspecialchars($body2) . "</pre>";

        if ($script->getStatusCode() == 200 && $body1 !== $body2) {
            $result->resultClass = TestResult::SUCCESS;
        } else {
            $result->resultClass = TestResult::FAILURE;
        }

        return $result;
    }

    public function test6() {
        $random = $this->getResponse('/random_file.htm');

        $result = new TestResult();

        $result->name = "404-Anfrage";

        $result->expected  = "Status: 404";

        $result->actual  = "Status: " . $random->getStatusCode();
        $result->actual .= "<pre>" . htmlspecialchars($random->getBody()) . "</pre>";

        if ($random->getStatusCode() == 404) {
            $result->resultClass = TestResult::SUCCESS;
        } else {
            $result->resultClass = TestResult::FAILURE;
        }

        return $result;
    }

    /**
     * @return ResponseInterface
     */
    private function getResponse($target, $options = array()) {
        return $this->client->request('GET', $this->url . $target,
            array_merge(['allow_redirects' => false, 'http_errors' => false], $options));
    }
}

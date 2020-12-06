<?php

declare(strict_types=1);

namespace AmGraphQl\App;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Socket\Server;
use Laminas\Http\Headers;
use Magento\Framework\App;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\ObjectManager\ConfigLoaderInterface;
use Magento\Framework\ObjectManagerInterface;
use Monolog\Logger;
use function Amp\ByteStream\getStdout;

class AsyncHttp implements \Magento\Framework\AppInterface
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ConfigLoaderInterface
     */
    private $configLoader;

    /**
     * @var State
     */
    private $state;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var \Magento\GraphQl\Controller\GraphQl
     */
    private $endpoint;

    public function __construct(
        ObjectManagerInterface $objectManager,
        ConfigLoaderInterface $configLoader,
        State $state
    ) {
        $this->objectManager = $objectManager;
        $this->configLoader = $configLoader;
        $this->state = $state;
        $logHandler = new StreamHandler(getStdout());
        $logHandler->setFormatter(new ConsoleFormatter);
        $this->logger = new Logger('server');
        $this->logger->pushHandler($logHandler);
    }

    public function launch()
    {
        $this->state->setAreaCode(Area::AREA_GRAPHQL);
        $this->objectManager->configure($this->configLoader->load(Area::AREA_GRAPHQL));
        $this->endpoint = $this->objectManager->get(\Magento\GraphQl\Controller\GraphQl::class);

        \Amp\Loop::run(function () {
            $sockets = [
                Server::listen("0.0.0.0:1337"),
                Server::listen("[::]:1337"),
            ];

            $server = new HttpServer($sockets, new CallableRequestHandler(function (Request $request) {
                /** @var \Magento\Framework\App\Request\Http $magentoRequest */
                $magentoRequest = $this->objectManager->get(\Magento\Framework\App\Request\Http::class);
                $magentoRequest->setMethod($request->getMethod());
                $newHeaders = new Headers();
                $newHeaders->addHeaders($request->getHeaders());
                $magentoRequest->setHeaders($newHeaders);

                if ($request->getMethod() === 'GET') {
                    parse_str(parse_url((string)$request->getUri())['query'] ?? '', $params);
                    $magentoRequest->setParams($params);
                } else {
                    $requestBody = '';
                    while (null != $chunk = yield $request->getBody()->read()) {
                        $requestBody .= $chunk;
                    }
                    $magentoRequest->setContent($requestBody);
                }

                return new Response(Status::OK, [
                    "content-type" => "text/plain; charset=utf-8"
                ], $this->endpoint->dispatch($magentoRequest)->getContent());
            }), $this->logger);

            yield $server->start();

            \Amp\Loop::onSignal(SIGINT, function (string $watcherId) use ($server) {
                \Amp\Loop::cancel($watcherId);
                yield $server->stop();
            });
        });
    }

    public function catchException(App\Bootstrap $bootstrap, \Exception $exception)
    {
        // so sad
    }
}
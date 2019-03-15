<?php
 /*
 * This file is part of ehaomiao/slim.
 *
 * (c) Haomiao Inc. <dev@ehaomiao.com>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace Haomiao\Slim;

use Haomiao\Lib\Middleware\Cors;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

use Slim\Exception\SlimException;
use Slim\Exception\MethodNotAllowedException;
use Slim\Exception\NotFoundException;

use Haomiao\Slim\Exception\MethodNotAllowed;
use Haomiao\Slim\Exception\NotFound;
use Haomiao\Slim\Exception\Exception as HaomiaoException;
use Haomiao\Slim\Exception\Message as HaomiaoMessage;

/**
 * Application class
 * 
 * 使用illuminate的container，借助其构造器依赖注入能力
 */
abstract class Application extends \Slim\App
{
    /**
     * Create new application
     *
     * @param ContainerInterface|array $container Either a ContainerInterface or an associative array of app settings
     * @throws InvalidArgumentException when no container is provided that implements ContainerInterface
     */
    public function __construct($container = [])
    {
        if (is_array($container)) {
            $container = new Container($container);
        }

        $container->instance(ContainerInterface::class, $container);
        $container->instance(SettingInterface::class, $this->generateSetting());

        parent::__construct($container);
        // 
        $this->__init();

        $container->singleton('settings', SettingInterface::class);
    }

    /**
     * __init
     * 
     * 需要额外的初始化，覆盖此方法
     *
     * @return void
     */
    protected function __init()
    { }

    /**
     * 启动
     *
     * @return void
     */
    final public function bootstrap()
    {
        // 生命周期函数__bootstrap
        $this->__bootstrap();
    }

    /**
     * __bootstrap
     * 
     * 需要额外的初始化，覆盖此方法
     *
     * @return void
     */
    protected function __bootstrap()
    {
        $this->registerRoutes();
    }

    /**
     * create setting
     */
    protected function generateSetting(): SettingInterface
    {
        $settings = require_once __DIR__  . '/../settings.php';

        return new Setting($settings);
    }

    /**
     * 初始化路由
     *
     * @return void
     */
    protected function registerRoutes()
    { }

    /**
     * Call relevant handler from the Container if needed. If it doesn't exist,
     * then just re-throw.
     *
     * @param  \Exception $e
     * @param  ServerRequestInterface $request
     * @param  ResponseInterface $response
     *
     * @return ResponseInterface
     * @throws \Exception if a handler is needed and not found
     */
    protected function handleException(
        \Exception $e,
        ServerRequestInterface $request,
        ResponseInterface $response
    ) {
        // 转换slim抛出的异常
        if ($e instanceof MethodNotAllowedException) {
            $e = new MethodNotAllowed($e->getAllowedMethods(), $e->getRequest(), $e->getResponse());
        } elseif ($e instanceof NotFoundException) {
            $e = new NotFound($e->getRequest(), $e->getResponse());
        } elseif ($e instanceof SlimException) {
            $e = new HaomiaoException($e->getMessage(), $e->getRequest(), $e->getResponse());
        }

        $setting = $this->getContainer()->get('settings');

        foreach ($setting->throwableHandlers as $targetClass => $handlerClass) {
            // 
            if ($e instanceof $targetClass) {

                $container = $this->getContainer();

                $handler = $container->make($handlerClass);

                $handler->setThrowable($e);

                if ($e instanceof HaomiaoException || $e instanceof HaomiaoMessage) {
                    if ($e->request) {
                        $request = $e->request;
                    }
                    if ($e->response) {
                        $response = $e->response;
                    }
                }

                // TODO 临时
                $response = (new Cors(config('app_cors')))($request, $response, function ($request, $response) {
                    return $response;
                });

                try {
                    return $handler->handle($request, $response);
                } catch (\Exception $ex) {
                    $this->handleException($ex, $request, $response);
                }
            }
        }

        // TODO 临时
        $response = (new Cors(config('app_cors')))($request, $response, function ($request, $response) {
            return $response;
        });

        return parent::handleException($e, $request, $response);
    }

    /**
     * Send the response to the client
     *
     * @param ResponseInterface $response
     */
    public function respond(ResponseInterface $response)
    {
        // Send response
        if (!headers_sent()) {
            // Headers
            foreach ($response->getHeaders() as $name => $values) {
                $first = stripos($name, 'Set-Cookie') === 0 ? false : true;
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), $first);
                    $first = false;
                }
            }

            // Set the status _after_ the headers, because of PHP's "helpful" behavior with location headers.
            // See https://github.com/slimphp/Slim/issues/1730

            // Status
            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ), true, $response->getStatusCode());
        }

        // Body
        if (!$this->isEmptyResponse($response)) {
            $body = $response->getBody();
            if ($body->isSeekable()) {
                $body->rewind();
            }
            $settings       = $this->getContainer()->get('settings');
            $chunkSize      = $settings['responseChunkSize'];

            $contentLength  = $response->getHeaderLine('Content-Length');
            if (!$contentLength) {
                $contentLength = $body->getSize();
            }

            $offset = 0;
            $contentRange = $response->getHeaderLine('Content-Range');
            if ($contentRange) {
                if (preg_match('#(\\d+)-(\\d+)/(\\d+)#', $contentRange, $matches)) {
                    $offset = (int)$matches[1];
                }
            }
            $body->seek($offset);

            if (isset($contentLength)) {
                $amountToRead = $contentLength;
                while ($amountToRead > 0 && !$body->eof()) {
                    $data = $body->read(min((int)$chunkSize, (int)$amountToRead));
                    echo $data;

                    $amountToRead -= strlen($data);

                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            } else {
                while (!$body->eof()) {
                    echo $body->read((int)$chunkSize);
                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            }
        }
    }

}

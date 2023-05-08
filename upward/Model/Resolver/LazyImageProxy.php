<?php

namespace Silksoftwarecorp\Upward\Model\Resolver;
use Laminas\Http\Client;
use Magento\Upward\Definition;

class LazyImageProxy extends \Magento\Upward\Resolver\Proxy
{

     /**
     * {@inheritdoc}
     */
    public function resolve($definition)
    {
        if (!$definition instanceof Definition) {
            throw new \InvalidArgumentException('$definition must be an instance of ' . Definition::class);
        }

        $target          = $this->getIterator()->get('target', $definition);
    
        $ignoreSSLErrors = $definition->has('ignoreSSLErrors')
            ? $this->getIterator()->get('ignoreSSLErrors', $definition)
            : false;
        $request            = new \Laminas\Http\PhpEnvironment\Request();
        $originalRequestURI = clone $request->getUri();
        $request->setUri($target);
        //change Repeat URL query params
        $request->getUri()->setPath($originalRequestURI->getPath());
        $requestHeaders = $request->getHeaders();
        if ($requestHeaders && $requestHeaders->has('Host')) {
            $requestHeaders->removeHeader($request->getHeader('Host'));
            $requestHeaders->addHeaderLine('Host', parse_url($target, \PHP_URL_HOST));
        }

        $client = new Client(null, [
            'adapter'     => Client\Adapter\Curl::class,
            'curloptions' => [
                \CURLOPT_SSL_VERIFYHOST => $ignoreSSLErrors ? 0 : 2,
                \CURLOPT_SSL_VERIFYPEER => !$ignoreSSLErrors,
            ],
        ]);

        return $client->send($request);
    }
}

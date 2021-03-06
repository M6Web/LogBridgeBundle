<?php

namespace M6Web\Bundle\LogBridgeBundle\Config;

use Psr\Log\LogLevel;
use Symfony\Component\Routing\RouterInterface;

/**
 * FilterParser
 */
class FilterParser
{
    const DEFAULT_LEVEL = LogLevel::INFO;

    /** @var array */
    protected $allowedLevels = [
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::INFO,
        LogLevel::DEBUG,
    ];

    /** @var RouterInterface */
    protected $router;

    /** @var string */
    protected $filterClass;

    /**
     * __construct
     *
     * @param RouterInterface $router Router service
     */
    public function __construct(RouterInterface $router = null)
    {
        $this->router = $router;
        $this->filterClass = '';
    }

    /**
     * createFilter
     *
     * @param string $name Filter name
     *
     * @return Filter
     */
    protected function createFilter($name)
    {
        if ($this->filterClass) {
            return (new \ReflectionClass($this->filterClass))->newInstanceArgs(['name' => $name]);
        }

        return new Filter($name);
    }

    /**
     * isRoute
     *
     * @param string $name Route name
     *
     * @return bool
     */
    protected function isRoute($name)
    {
        return $this->router->getRouteCollection()->get($name) ? true : false;
    }

    /**
     * parse
     *
     * @param string $name   name
     * @param array  $config configuration
     *
     * @throws ParseException
     *
     * @internal param array $filterConfig
     *
     * @return Filter
     */
    public function parse($name, array $config)
    {
        $filter = $this->createFilter($name);

        if (!array_key_exists('route', $config) || !array_key_exists('method', $config) || !array_key_exists('status', $config)) {
            throw new ParseException(sprintf('Undefined "route", "method" or "status" parameter from filter "%s"', $name));
        }

        if (!array_key_exists('level', $config)) {
            $config['level'] = self::DEFAULT_LEVEL;
        }

        $this->parseRoute($filter, $config['route']);
        $this->parseMethod($filter, $config['method']);
        $this->parseStatus($filter, $config['status']);
        $this->parseLevel($filter, $config['level']);

        $filter->setOptions(isset($config['options']) ? $config['options'] : []);

        return $filter;
    }

    /**
     * parseRoute
     *
     * @param Filter $filter Filter
     * @param mixed  $route  Route parameter value
     *
     * @throws ParseException
     */
    protected function parseRoute(Filter $filter, $route)
    {
        if (!is_null($route) && !$this->isRoute($route)) {
            throw new ParseException(sprintf('Undefined route "%s" from router service', $route));
        }

        $filter->setRoute($route);
    }

    /**
     * parseMethod
     *
     * @param Filter $filter Filter
     * @param mixed  $method Method parameter value
     *
     * @throws ParseException
     */
    protected function parseMethod(Filter $filter, $method)
    {
        if (!is_array($method) && !is_null($method)) {
            throw new ParseException(sprintf('Unrecognized value "%s" from method parameter', $method));
        }

        $filter->setMethod($method);
    }

    /**
     * parseStatus
     *
     * @param Filter $filter Filter
     * @param mixed  $status Status parameter value
     *
     * @throws ParseException
     */
    protected function parseStatus(Filter $filter, $status)
    {
        if (!is_array($status) && !is_null($status)) {
            throw new ParseException(sprintf('Unrecognized value "%s" from status parameter', $status));
        }

        $filter->setStatus($status);
    }

    /**
     * parseLevel
     *
     * @param Filter $filter Filter
     * @param mixed  $level  Level parameter value
     *
     * @throws ParseException
     */
    protected function parseLevel(Filter $filter, $level)
    {
        if (!is_string($level) && !is_null($level)) {
            throw new ParseException(sprintf('Unrecognized value "%s" from level parameter', $level));
        }

        if (!in_array($level, $this->allowedLevels)) {
            throw new ParseException(sprintf('Invalid value "%s" from level parameter, allowed %s', $level, implode(', ', $this->allowedLevels)));
        }

        $filter->setLevel($level);
    }

    /**
     * setRouter
     *
     * @param RouterInterface $router Router
     *
     * @return FilterParser
     */
    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;

        return $this;
    }

    /**
     * setFilterClass
     *
     * @param string $filterClass Filter class name
     *
     * @return FilterParser
     *
     * @throws \RuntimeException
     */
    public function setFilterClass($filterClass)
    {
        $reflection = new \ReflectionClass($filterClass);

        if (
            !$reflection->isInstantiable()
             || !$reflection->isSubclassOf('M6Web\Bundle\LogBridgeBundle\Config\Filter')
        ) {
            throw new \RuntimeException(sprintf('"%s" is not instantiable or is not a subclass of "M6Web\Bundle\LogBridgeBundle\Config\Filter"', $filterClass));
        }

        $this->filterClass = $filterClass;

        return $this;
    }

    /**
     * getFilterClass
     *
     * @return string
     */
    public function getFilterClass()
    {
        return $this->filterClass;
    }
}

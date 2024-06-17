<?php 

declare(strict_types=1);

namespace Framework;

class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private array $errorHandler;

    public function add(string $method, string $path, array $controller)
    {
        $path = $this->normalizePath($path);

        $regexPath = preg_replace('#{[^/]+}#', '([^/]+)', $path);
        
        $this->routes[] = [
            'path' => $path,
            'method' => strtoupper($method),
            'controller' => $controller,
            'middlewares' => [],
            'regexPath' =>$regexPath
        ];
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path, '/');
        $path = "/{$path}/";
        $path = preg_replace('#[/]{2,}#', '/', $path);

        return $path;
    }

    public function dispatch(string $path, string $method, Container $container = null)
    {
        $path = $this->normalizePath($path);
        $method = strtoupper($_POST['_METHOD'] ?? $method);

        foreach($this->routes as $route) {
            if (!preg_match("#^{$route['regexPath']}$#", $path, $paramValues) ||  
            $route['method'] !== $method // returns a single result
            ) {
                continue;
            }

            array_shift($paramValues); //remove the first item in an array

            preg_match_all('#{([^/]+)}#', $route['path'], $paramKeys); // returns all possible result

            $paramKeys = $paramKeys[1];

            $params = array_combine($paramKeys, $paramValues); // combine the key and value to return the route and route parameter as key-value pair

            [$class, $function] = $route['controller'];

            $controllerInstance = $container ? 
            $container->resolve($class) : //resolve method will return an instance with dependency to the controller
            new $class;
    
            $action = fn () => $controllerInstance->{$function}($params); //middleware to be called is stored in the action variable

            $allMiddleware = [...$route['middlewares'], ...$this->middlewares]; //join the existing middleware with the route middleware using spread operator

            foreach($allMiddleware as $middleware) {
                $middlewareInstance = $container ?  
                    $container->resolve($middleware) : //checks for the existence of container
                        new $middleware;
                $action = fn () => $middlewareInstance->process($action);
            }

            $action();

            return;
        }

        $this->dispatchNotFound($container);
    }

    public function addMiddleware(string $middleware) { //Middleware is defined as classes in order for the container to have access to container to inject dependency
        $this->middlewares[] = $middleware;
    }

    public function addRouteMiddleware(string $middleware) {
        $lastRouteKey = array_key_last($this->routes);
        $this->routes[$lastRouteKey]['middlewares'][]= $middleware;
    }

    public function setErrorHandler(array $controller) {
        $this->errorHandler = $controller;
    }

    public function dispatchNotFound(?Container $container) //? indicates allow null value
    {
        [$class, $function] = $this->errorHandler;

        $controllerInstance = $container ? $container->resolve($class) : new $class;

        $action = fn () => $controllerInstance->$function();

        foreach($this->middlewares as $middleware) {
            $middlewareInstance = $container ? $container->resolve($middleware) : new $middleware;   
            $action = fn() => $middlewareInstance->process($action);
        }

        $action();
        //this method creates an instance of a specific controller whereas
        //the dispatch method selects a controller based on the route
    }
}
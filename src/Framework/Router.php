<?php 

declare(strict_types=1);

namespace Framework;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    public function add(string $method, string $path, array $controller)
    {
        $path = $this->normalizePath($path);
        
        $this->routes[] = [
            'path' => $path,
            'method' => strtoupper($method),
            'controller' => $controller,
            'middlewares' => []
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
        $method = strtoupper($method);

        foreach($this->routes as $route) {
            if (!preg_match("#^{$route['path']}$#", $path) || 
            $route['method'] !== $method
            ) {
                continue;
            }

            [$class, $function] = $route['controller'];

            $controllerInstance = $container ? 
            $container->resolve($class) : //resolve method will return an instance with dependency to the controller
            new $class;
    
            $action = fn () => $controllerInstance->{$function}(); //middleware to be called is stored in the action variable

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
    }

    public function addMiddleware(string $middleware) { //Middleware is defined as classes in order for the container to have access to container to inject dependency
        $this->middlewares[] = $middleware;
    }

    public function addRouteMiddleware(string $middleware) {
        $lastRouteKey = array_key_last($this->routes);
        $this->routes[$lastRouteKey]['middlewares'][]= $middleware;
    }
}
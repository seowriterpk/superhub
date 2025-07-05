
<?php
namespace VidSocial\Core;

/**
 * Router Class
 * Handles URL routing and controller dispatch
 */
class Router
{
    private $routes = [];
    private $namedRoutes = [];
    
    public function get(string $pattern, string $controller, ?string $name = null): void
    {
        $this->addRoute('GET', $pattern, $controller, $name);
    }
    
    public function post(string $pattern, string $controller, ?string $name = null): void
    {
        $this->addRoute('POST', $pattern, $controller, $name);
    }
    
    private function addRoute(string $method, string $pattern, string $controller, ?string $name): void
    {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'controller' => $controller,
            'name' => $name
        ];
        
        if ($name) {
            $this->namedRoutes[$name] = $pattern;
        }
    }
    
    public function handleRequest(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            $pattern = $this->convertPatternToRegex($route['pattern']);
            
            if (preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remove full match
                $this->dispatch($route['controller'], $matches);
                return;
            }
        }
        
        // 404 Not Found
        $this->handle404();
    }
    
    private function convertPatternToRegex(string $pattern): string
    {
        // Convert {param} to named capture groups
        $pattern = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $pattern);
        
        // Handle optional parameters {param?}
        $pattern = preg_replace('/\{([^}]+)\?\}/', '(?P<$1>[^/]*)', $pattern);
        
        return '#^' . $pattern . '$#';
    }
    
    private function dispatch(string $controllerAction, array $params): void
    {
        [$controllerName, $action] = explode('@', $controllerAction);
        $controllerClass = 'VidSocial\\Controllers\\' . $controllerName;
        
        if (!class_exists($controllerClass)) {
            throw new \Exception("Controller {$controllerClass} not found");
        }
        
        $controller = new $controllerClass();
        
        if (!method_exists($controller, $action)) {
            throw new \Exception("Method {$action} not found in {$controllerClass}");
        }
        
        // Convert named parameters to method arguments
        $reflection = new \ReflectionMethod($controller, $action);
        $methodParams = $reflection->getParameters();
        $args = [];
        
        foreach ($methodParams as $param) {
            $paramName = $param->getName();
            if (isset($params[$paramName])) {
                $args[] = $params[$paramName];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                $args[] = null;
            }
        }
        
        call_user_func_array([$controller, $action], $args);
    }
    
    private function handle404(): void
    {
        http_response_code(404);
        
        $controller = new \VidSocial\Controllers\ErrorController();
        $controller->notFound();
    }
    
    public function generateUrl(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \Exception("Route {$name} not found");
        }
        
        $url = $this->namedRoutes[$name];
        
        foreach ($params as $key => $value) {
            $url = str_replace('{' . $key . '}', $value, $url);
            $url = str_replace('{' . $key . '?}', $value, $url);
        }
        
        // Remove unused optional parameters
        $url = preg_replace('/\{[^}]+\?\}/', '', $url);
        
        return $url;
    }
}

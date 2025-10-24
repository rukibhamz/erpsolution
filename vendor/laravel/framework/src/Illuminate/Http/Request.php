<?php

namespace Illuminate\Http;

class Request
{
    protected $server;
    protected $get;
    protected $post;
    protected $files;
    protected $cookies;
    protected $headers;
    
    public function __construct()
    {
        $this->server = $_SERVER;
        $this->get = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
        $this->headers = $this->getHeaders();
    }
    
    public static function capture()
    {
        return new static();
    }
    
    public function getRequestUri()
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }
    
    public function getMethod()
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }
    
    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return $this->get;
        }
        
        return $this->get[$key] ?? $default;
    }
    
    public function post($key = null, $default = null)
    {
        if ($key === null) {
            return $this->post;
        }
        
        return $this->post[$key] ?? $default;
    }
    
    public function input($key = null, $default = null)
    {
        $input = array_merge($this->get, $this->post);
        
        if ($key === null) {
            return $input;
        }
        
        return $input[$key] ?? $default;
    }
    
    public function has($key)
    {
        return $this->input($key) !== null;
    }
    
    public function file($key = null)
    {
        if ($key === null) {
            return $this->files;
        }
        
        return $this->files[$key] ?? null;
    }
    
    public function hasFile($key)
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }
    
    public function header($key = null, $default = null)
    {
        if ($key === null) {
            return $this->headers;
        }
        
        return $this->headers[$key] ?? $default;
    }
    
    public function cookie($key = null, $default = null)
    {
        if ($key === null) {
            return $this->cookies;
        }
        
        return $this->cookies[$key] ?? $default;
    }
    
    public function isMethod($method)
    {
        return strtoupper($this->getMethod()) === strtoupper($method);
    }
    
    public function isAjax()
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }
    
    public function isJson()
    {
        return strpos($this->header('Content-Type', ''), 'application/json') !== false;
    }
    
    public function wantsJson()
    {
        return $this->isJson() || $this->isAjax();
    }
    
    public function url()
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        $host = $this->getHost();
        $port = $this->getPort();
        
        $url = $scheme . '://' . $host;
        
        if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
            $url .= ':' . $port;
        }
        
        return $url;
    }
    
    public function fullUrl()
    {
        return $this->url() . $this->getRequestUri();
    }
    
    public function getHost()
    {
        return $this->server['HTTP_HOST'] ?? $this->server['SERVER_NAME'] ?? 'localhost';
    }
    
    public function getPort()
    {
        return $this->server['SERVER_PORT'] ?? 80;
    }
    
    public function isSecure()
    {
        return isset($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off';
    }
    
    protected function getHeaders()
    {
        $headers = [];
        
        foreach ($this->server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('_', '-', substr($key, 5));
                $headers[$header] = $value;
            }
        }
        
        return $headers;
    }
}

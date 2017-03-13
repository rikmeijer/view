<?php
namespace pulledbits\View\File;


class Template implements \pulledbits\View\Template {
    private $templatePath;
    private $layoutsPath;
    private $cachePath;

    private $helpers;

    public function __construct(string $templatePath, string $layoutsPath, string $cachePath) {
        $this->templatePath = $templatePath;
        $this->layoutsPath = $layoutsPath;
        $this->cachePath = $cachePath;
        $this->helpers = [];
    }

    private function sub(string $templateIdentifier): \pulledbits\View\Template
    {
        $template = new self(dirname($this->templatePath) . DIRECTORY_SEPARATOR . $templateIdentifier . '.php', $this->layoutsPath, $this->cachePath);
        $template->helpers = $this->helpers;
        return $template;
    }

    private function layout(string $layoutIdentifier) : \pulledbits\View\Layout
    {
        return new Layout($this->layoutsPath . DIRECTORY_SEPARATOR . $layoutIdentifier . '.php');
    }

    private function escape(string $unsafestring) {
        return htmlentities($unsafestring);
    }

    public function __call(string $identifier, array $arguments): string
    {
        if (array_key_exists($identifier, $this->helpers)) {
            return call_user_func_array($this->helpers[$identifier], $arguments);
        }
        return '';
    }

    public function registerHelper(string $identifier, callable $callback)
    {
        $this->helpers[$identifier] = \Closure::bind($callback, $this, __CLASS__);
    }

    public function render(array $parameters) {
        $variables = [];
        foreach ($parameters as $parameterIdentifier => $parameter) {
            if (is_callable($parameter)) {
                $this->registerHelper($parameterIdentifier, $parameter);
            } else {
                $variables[$parameterIdentifier] = $parameter;
            }
        }
        extract($variables);

        $cacheFile = $this->cachePath . DIRECTORY_SEPARATOR . sha1_file($this->templatePath) . '.php';
        if (file_exists($cacheFile) === false) {
            $contents = file_get_contents($this->templatePath);
            file_put_contents($cacheFile, preg_replace('/<\?=\s?(.*?)[;\s]*\?>/', '<?=$this->escape($1);?>', $contents));
        }
        include $cacheFile;
    }
}
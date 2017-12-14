<?php
namespace pulledbits\View\File;

use pulledbits\View\TemplateInstance;

/**
 * Class Template
 * @package pulledbits\View\File
 */
class Template implements \pulledbits\View\Template
{
    /**
     * @var string
     */
    private $templatePath;

    /**
     * @var array
     */
    private $helpers;

    /**
     * Template constructor.
     * @param string $templatePath
     */
    public function __construct(string $templatePath)
    {
        $this->templatePath = $templatePath;
        $this->helpers = [];
    }

    /**
     * @param array $parameters
     * @return resource
     */
    public function capture(array $parameters)
    {
        ob_start();
        $this->render($parameters);
        return ob_get_clean();
    }

    /**
     * @param string $unsafestring
     * @return string
     */
    public function escape(string $unsafestring)
    {
        return htmlentities($unsafestring);
    }

    /**
     * @param string $identifier
     * @param array $arguments
     * @return string
     */
    public function __call(string $identifier, array $arguments): string
    {
        if (array_key_exists($identifier, $this->helpers) === false) {
            trigger_error('Call to undefined method ' . __CLASS__ . '::' . $identifier, E_USER_ERROR);
        }

        $helperReflection = new \ReflectionFunction($this->helpers[$identifier]);
        if ($helperReflection->hasReturnType() === false) {
            call_user_func_array($this->helpers[$identifier], $arguments);
            return '';
        }

        switch ($helperReflection->getReturnType()) {
            case 'string':
                return $this->escape(call_user_func_array($this->helpers[$identifier], $arguments));

            case 'void':
                call_user_func_array($this->helpers[$identifier], $arguments);
                return '';

            default:
                ob_start();
                call_user_func_array($this->helpers[$identifier], $arguments);
                ob_end_clean();
                return '';
        }
    }

    /**
     * @param string $identifier
     * @param callable $callback
     */
    public function registerHelper(string $identifier, callable $callback) : void
    {
        $this->helpers[$identifier] = \Closure::bind($callback, $this, __CLASS__);
    }

    public function render(array $parameters): void
    {
        $variables = [];
        foreach ($parameters as $parameterIdentifier => $parameter) {
            if (is_callable($parameter)) {
                $this->registerHelper($parameterIdentifier, $parameter);
            } else {
                $variables[$parameterIdentifier] = $parameter;
            }
        }
        extract($variables);

        include $this->templatePath;
    }

    public function prepare(array $parameters) : TemplateInstance {
        return new TemplateInstance($this, $parameters);
    }
}
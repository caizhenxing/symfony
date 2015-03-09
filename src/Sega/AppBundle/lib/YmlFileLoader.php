<?php
namespace Dcs;

// ymlファイルをロードして、連想配列でアクセスできるようにする

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;

class YmlFileLoader extends FileLoader {

    /**
     * Constructor.
     * @param string $base_path Yaml file path
     */
    function __construct($base_path = NULL) {
        if($base_path != NULL) {
            $locator = new FileLocator($base_path);
        }
        else{
            $default_path = __DIR__.'/../Resources/config';
            $locator = new FileLocator($default_path);
        }
        parent::__construct($locator);
    }

    /**
     * Loads a Yaml file As Array.
     * @param string $file A Yaml file path
     * @return array
     */
    public function load($file, $type = null) {
        $path = $this->locator->locate($file);
        $contents = Yaml::parse($path);

        if (null === $contents || !is_array($contents)) {
            $contents = array();
        }
        return $contents;
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     * @return bool
     */
    public function supports($resource, $type = null) {
        return is_string($resource) && 'yml' === pathinfo($resource, PATHINFO_EXTENSION) && (!$type || 'yaml' === $type);
    }
    
}

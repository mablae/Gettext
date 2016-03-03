<?php

namespace Gettext\Extractors;

use Gettext\Translations;
use Twig_Loader_String;
use Twig_Environment;

/**
 * Class to get gettext strings from twig files returning arrays.
 */
class Twig extends Extractor implements ExtractorInterface
{
    /**
     * Twig instance.
     *
     * @var Twig_Environment
     */
    protected static $twig;

    /**
     * Kernel
     *
     * @var \AppKernel
     */
    protected static $kernel;

    /**
     * {@inheritdoc}
     */
    public static function fromString($string, Translations $translations = null, $file = '')
    {
        self::createKernel();
        self::createTwig();

        $string = self::$twig->getLoader()->getSource($file);
        $string = self::$twig->compileSource($string, $file);

        // find all linefeeds in buffer
        $reg = preg_match_all('/ call_user_func_array\(\$this->env->getFunction\(\'__\'\)->getCallable\(\), array\("(.*?)"\)\)/s', $string, $sourceStrings, PREG_OFFSET_CAPTURE );
        foreach ($sourceStrings[1] as $key => $sourceString) {

            $line = self::getLine($string, $sourceStrings[0][$key][1]);
            $fileReference = str_replace(realpath(self::$kernel->getContainer()->getParameter('kernel.root_dir') . '/../'). '/', "", $file);
            $translations->insert('', $sourceString[0])->addReference($fileReference, $line);

        }

        return $translations;
    }


    public static function getLine($string, $offset) {

        $subString = substr($string, 0, $offset);
        $line = substr_count( $subString, "\n" ) +1;

        return $line;

    }

    public static function  createKernel() {

        if (!isset(self::$kernel)) {
            self::$kernel = new \AppKernel('prod', false);
            self::$kernel->boot();

        }

    }

    public static function  createTwig() {

        if (!isset(self::$twig)) {
            self::$twig = self::$kernel->getContainer()->get('twig');
        }

    }

    /**
     * Initialise Twig if it isn't already, and add a given Twig extension.
     * This must be called before calling fromString().
     *
     * @param mixed $extension Already initialised extension to add
     */
    public static function addExtension($extension)
    {
        // initialise twig
        if (!isset(self::$twig)) {
            $twigCompiler = new Twig_Loader_String();

            self::$twig = new Twig_Environment($twigCompiler);
        }

        if (!self::checkHasExtensionByClassName($extension)) {
            self::$twig->addExtension(new $extension());
        }
    }

    /**
     * Checks if a given Twig extension is already registered or not.
     *
     * @param  string   Name of Twig extension to check
     *
     * @return bool Whether it has been registered already or not
     */
    protected static function checkHasExtensionByClassName($className)
    {
        foreach (self::$twig->getExtensions() as $extension) {
            if ($className == get_class($extension)) {
                return true;
            }
        }

        return false;
    }
}

<?php
namespace Paxx\Gettext;

use \Config;
use \File;
use \Log;

use Paxx\Gettext\Exceptions\SetLocaleException;

class Gettext {
    /**
     * The current locale
     *
     * @var string
     */
    protected $locale = null;

    /**
     * The current encoding
     *
     * @var array
     */
    protected $encoding = null;
	
    /**
     * The target env. variable.
     *
     * @var constant
     */
    protected $target = null;

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Method to set the target
     *
     * @param constant $target
     * @return \Paxx\Gettext\Gettext
     */
    public function setTarget($target)
    {
        $this->target = $target;
        return $this;
    }

    /**
     * Method to set the textdomain
     *
     * @param string $textdomain
     * @param string $path
     * @return \Paxx\Gettext\Gettext
     */
    public function setTextdomain($textdomain, $path)
    {
        // full path to localization messages
        $full_path = base_path('resources') . DIRECTORY_SEPARATOR . $path;

        // sanity check - path must exist relative to app/ folder
        if (!File::isDirectory($full_path)) File::makeDirectory($full_path);

        // bind text domain
        bindtextdomain($textdomain, $full_path);

        // set text domain
        textdomain($textdomain);

        // We might need to specifiy this?
        // bind_textdomain_codeset($textdomain, $this->encoding);

        return $this;
    }

    /**
     * Method to set the encoding
     *
     * @param mixed[] $encoding
     * @return \Paxx\Gettext\Gettext
     */
    public function setEncoding($encoding)
    {
        // The only thing we need to do here is to set the prefered encoding and make sure that it's an array
        $this->encoding = (array)$encoding;

        return $this;
    }

    /**
     * Method to set the locale
     *
     * @param string $locale
     * @return \Paxx\Gettext\Gettext
     * @throws \Paxx\Gettext\Exceptions\SetLocaleException;
     */
    public function setLocale($locale='') 
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') return $this;

        if(!putenv("LANGUAGE=" . $locale)) {
            Log::warning(sprintf('Could not set the ENV variable LANGUAGE = %s', $locale));
        }

        if(!putenv("LANG=" . $locale)) {
            Log::warning(sprintf('Could not set the ENV variable LANG = %s', $locale));
        }

        // Merge the locale with the encoding
        $locales = array_map(function($encoding) use($locale) {
            return $locale . '.' . $encoding;
        }, $this->encoding);

        // We'll add a new, empty row to make sure that windows users don't freak out
        $locales[] = $locale;

        if(!call_user_func_array('setlocale', array_merge(array($this->target),$locales))) {
            throw new SetLocaleException('The locale(s) [' . implode($locales, ',') . '] could not be set');
        }

        // Reset the numeric
        if(Config::get('gettext::config.reset_numeric')) {
            setlocale(LC_NUMERIC, 'C');
        }

        return $this;
    }

}

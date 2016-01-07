<?php

namespace Paxx\Gettext\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\ProcessBuilder;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\Filesystem\Filesystem;
use Config;
use File;
use SplFileInfo;

use Paxx\Gettext\Exceptions\XgettextException;
use Paxx\Gettext\Exceptions\NoViewsFoundException;
use Paxx\Gettext\Exceptions\MsgmergeException;

class GettextCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'gettext';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compiles blade-views, extracts and merges gettext translations and creates a folder structure for localisation';

    /**
     * Symonfy procbuilder
     *
     * @var null
     */ 
    protected $procBuilder = null;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->procBuilder = new ProcessBuilder;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire ()
    {
        $views_folder = base_path('resources/views') . DIRECTORY_SEPARATOR;
        $cache_folder = base_path($this->option('cache')) . DIRECTORY_SEPARATOR;

        if (!File::exists($cache_folder)) {
            File::makeDirectory($cache_folder);
        } else {
            File::cleanDirectory($cache_folder);
        }

        /** @var SplFileInfo[] $views */
        $views = File::allFiles($views_folder);

        // Compile the files:
        $compiledPathToSourcePath = $this->compile($views, $cache_folder);

        // Remove the blade-templates since they are in the compiled views
        $views = array_filter($views, function(SplFileInfo $view) {
            return !(substr($view->getRealPath(), -9) === 'blade.php');
        });

        // Merge the view files with the compiled views
        $files = array_merge($views, File::allFiles($cache_folder));

        // Go through the additional files
        $additional_paths = $this->option('additional');

        // Convert to array if string
        if(!is_array($additional_paths)) {
            $additional_paths = explode(',', $additional_paths);
        }

        if(!empty($additional_paths)) {

            $additional_files = array();

            foreach($additional_paths as $path) {
                $additional_files = array_merge($additional_files, File::allFiles(base_path($path)));
            }

            // Merge the additional files with the view files
            $files = array_merge($files, $additional_files);
        }

        // Count the array to make sure we actually have something to do
        $count = count($files);

        if($count < 1) {
            throw new NoViewsFoundException("No files found in $views_folder and $cache_folder [and in the additional folder(s)]");
        }

        // Show some info
        $this->comment("\t[$count] view files found");

        /**

        Configuration for the xgettext

        You can read more about xgettext here: http://linux.die.net/man/1/xgettext

        */

        // Holder for the xgettext-arguments
        $xgettext = array();

        // Set the path and the binary        
        $xgettext[] = (($this->option('binary_path') == '') ? '' : $this->option('binary_path') . DIRECTORY_SEPARATOR) . $this->option('binary');
        
        // Store the output-file
        $output_file = base_path(Config::get('gettext.path')) . DIRECTORY_SEPARATOR . Config::get('gettext.textdomain') . '.pot';
        $xgettext[] = '--output=' . $output_file;

        // Since the language always will be PHP, we can 
        $xgettext[] = '--language=PHP';

        // Deterministic output!
        $xgettext[] = '--sort-output';

        // Place comment block with TAG (or those preceding keyword lines) in output file
        if($this->option('comments'))
            $xgettext[] = '--add-comments=' . $this->option('comments');

        // Write PO file even if empty 
        if($this->option('force_po') === true)
            $xgettext[] = '--force-po';

        // Do not leave references to the location where the gettext was found
        if($this->option('no_location')) 
            $xgettext[] = '--no-location';

        // Encoding of input files
        if($this->option('from_code'))
            $xgettext[] = '--from-code=' . $this->option('from_code');

        // Set copyright holder in output 
        if($this->option('author'))
            $xgettext[] = '--copyright-holder=' . $this->option('author');

        // Set package name
        if($this->option('package_name'))
            $xgettext[] = '--package-name=' . $this->option('package_name');

        // Set package version
        if($this->option('package_version'))
            $xgettext[] = '--package-version=' . $this->option('package_version');

        // Set report address for msgid bugs 
        if($this->option('email'))
            $xgettext[] = '--msgid-bugs-address=' . $this->option('email');

        // Keywords
        foreach($this->option('keywords') as $k)
            $xgettext[] = "--keyword=$k";

        // Merge the view-files with the xgettext-arguments array
        $xgettext = array_merge($xgettext, array_map(function(SplFileInfo $file) { return $file->getRealPath(); }, $files));

        // Use the Symfony\Component\Process\ProcessBuilder and set the arguments
        $this->procBuilder->setArguments($xgettext);

        // Execute the process
        $process = $this->procBuilder->getProcess();
        $process->run();

        // If not successful throw an error:
        if(!$process->isSuccessful()) {
            throw new XgettextException($process->getExitCode() . $process->getExitCodeText() . PHP_EOL . $process->getCommandLine());
        }

        $this->rebaseLocationsInComments($output_file, $compiledPathToSourcePath);

        // Show the user som info:
        $this->info("\tPOT file located in: $output_file\n");
        $this->info("\txgettext successfully executed!");

        // Check if we should do the msgmerge
        if($this->option('msgmerge')) 
           $this->merge($output_file);

        // Cleanup
        if($this->option('cleanup'))
            $this->cleanup();
    }

    /**
     * Method to clean up the cache-folder
     *
     * @return void
     */
    private function cleanup() {
        // Cache path
        $cache = base_path($this->option('cache')) . DIRECTORY_SEPARATOR;

        // Get all files
        $views = File::allFiles($cache);

        // Loop through all the files and delete them:
        foreach($views as $view) {
            File::delete($view);
        }
    }

    /**
     * Method to compile blade views
     *
     * @param SplFileInfo[] $views
     * @param string $output
     * @return string[string]
     */
    private function compile($views, $output) {
        // New BladeCompiler
        $blade = new BladeCompiler(new Filesystem, $output);

        // Keep only the blade-templates
        /** @var SplFileInfo[] $views */
        $views = array_filter($views, function($view) {
            return (substr($view, -9) === 'blade.php');
        });

        $compiledPathToSourcePath = array();

        foreach($views as $view) {
            $viewPath = $view->getRealPath();

            $blade->compile($viewPath);

            $compiledPath = realpath($blade->getCompiledPath($viewPath));
            $compiledPathToSourcePath[$compiledPath] = $viewPath;
        }

        return $compiledPathToSourcePath;
    }

    /**
     * Method to merge new template file with existing translation files
     *
     * @param string $templateFile
     * @return void
     */
    private function merge($templateFile) {
        // Get the config for msgmerge:
        $config = Config::get('gettext.msgmerge');

        $this->comment("\n\tTrying to combine template with existing translations...\n");

        // Get the path to locales
        $path = base_path(Config::get('gettext.path')) . DIRECTORY_SEPARATOR;

        // Get the locales
        $locales = Config::get('gettext.locales');
        $locales = !is_array($locales) ? array($locales) : $locales;

        // Shift the array down, so we don't create the first folder
        array_shift($locales);

        // Holder for the arguments
        $msgmerge = array();

        // Get the binary
        $msgmerge[] = (empty($config['binary_path']) ? '' : $config['binary_path'] . DIRECTORY_SEPARATOR) . $config['binary'];

        // Go through the locales and create the folders if they do not exists
        if(!empty($locales)) {
            foreach($locales as $locale) {
                $locale_dir = $path . $locale . DIRECTORY_SEPARATOR . 'LC_MESSAGES' . DIRECTORY_SEPARATOR;
                $resultFile = $locale_dir . Config::get('gettext.textdomain') . '.po';
                $tempFile = $locale_dir . 'temp.po';

                // Create the locale directory if it does not exist
                if(!File::isDirectory($locale_dir))
                    File::makeDirectory($locale_dir, 0775, true);

                // Check if we have the original POT-file:
                if(!File::exists($resultFile)) {
                    // No translation could be found, so we just copy the output into the folder
                    File::copy($templateFile, $resultFile);

                    // And since there is no need to merge the files together we can just continue the loop.
                    continue;
                }

                // If the file can be found, we need to create a temporary file:
                File::copy($resultFile, $tempFile);

                // msgmerge arguments
                $args = array(
                    $tempFile, // Previous translation file
                    $templateFile, // Template file
                    '--output-file=' . $resultFile, // And output it to the original
                    '--sort-output', // Deterministic output
                );

                // Use the symfony process-builder and execute msgmerge
                $this->procBuilder->setArguments(array_merge($msgmerge, $args));

                // Execute the process
                $process = $this->procBuilder->getProcess();
                $process->run();
            
                if($process->isSuccessful()) {
                    // Show the user some information
                    $this->info("\tMerged template with existing translations into $resultFile");

                    // Remove the temporary language-file
                    File::delete($tempFile);
                } else {
                    throw new MsgmergeException($process->getExitCode() . $process->getExitCodeText() . PHP_EOL . $process->getCommandLine());
                }
            }
        }

    }

    /**
     * Changes all paths present in the comments to be based on the application path, not the real filesystem.
     * This ensures that regenerated files are identical even when executed on another machine with a different
     * path to the source files.
     *
     * @param string $filename
     * @param string[string] $compiledPathToSourcePath
     */
    public function rebaseLocationsInComments($filename, $compiledPathToSourcePath) {
        $content = file_get_contents($filename);
        $basePath = base_path() . DIRECTORY_SEPARATOR;

        // Replace all full paths with relative paths.
        $pattern = '/^#: (?P<fullPath>.*?):(?P<lineNumber>\d+)\\n/m';
        $content = preg_replace_callback($pattern, function($matches) use ($basePath, $compiledPathToSourcePath) {
            $fullPath = $matches['fullPath'];
            $lineNumber = $matches['lineNumber'];

            $fullPath = array_get($compiledPathToSourcePath, $fullPath, $fullPath);
            $relativePath = substr($fullPath, strlen($basePath));
            return '#: ' . $relativePath . ':' . $lineNumber . "\n";
        }, $content);

        // Reorder all local paths
        $pattern = '/^\\n((?:#: .*\n)*)/m';
        $content = preg_replace_callback($pattern, function($matches) {
            $lines = explode("\n", $matches[1]);
            $lines = array_filter($lines);
            sort($lines);

            return "\n" . implode("\n", $lines) . "\n";
        }, $content);

        file_put_contents($filename, $content);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions ()
    {
        // Fetch the config
        $config = Config::get('gettext');

        $defaults = array(
            'cache'           => $config['cache'],
            'additional'      => $config['additional_paths'],
            'cleanup'         => $config['cleanup'],
            'msgmerge'        => $config['msgmerge']['enabled'],
            'binary'          => $config['xgettext']['binary'],
            'binary_path'     => $config['xgettext']['binary_path'],
            'comments'        => $config['xgettext']['comments'],
            'force_po'        => $config['xgettext']['force_po'],
            'no_location'     => $config['xgettext']['no_location'],
            'from_code'       => $config['xgettext']['from_code'],
            'author'          => $config['xgettext']['author'],
            'package_name'    => $config['xgettext']['package_name'],
            'package_version' => $config['xgettext']['package_version'],
            'email'           => $config['xgettext']['email'],
            'keywords'        => $config['xgettext']['keywords'],
        );

        // Return the options array
        return array(
            array('binary',          'b',  InputOption::VALUE_REQUIRED, 'The name of the xgettext binary', $defaults['binary']),
            array('binary_path',     'p',  InputOption::VALUE_REQUIRED, 'The path to the xgettext binary', $defaults['binary_path']),
            array('comments',        'c',  InputOption::VALUE_REQUIRED, 'The docblock text to scan for', $defaults['comments']),
            array('force_po',        'f',  InputOption::VALUE_REQUIRED, 'Force the creation of a .pot regardless of any translation strings found (bool)', $defaults['force_po']),
            array('no_location',     'nl', InputOption::VALUE_REQUIRED, 'Do not leave a location trail in the POT-file', $defaults['no_location']),
            array('cache',           'ca', InputOption::VALUE_REQUIRED, 'The folder in which the compiled blade views will end up', $defaults['cache']),
            array('additional',      'ad', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'If any additional files, this should be specified here', $defaults['additional']),
            array('binary',          'b',  InputOption::VALUE_REQUIRED, 'The name of the xgettext binary', $defaults['binary']),
            array('cleanup',         'cu', InputOption::VALUE_REQUIRED, 'Cleanup the view cache-folder', $defaults['cleanup']),
            array('msgmerge',        'mm', InputOption::VALUE_REQUIRED, 'Check if we should do the msgmerge', $defaults['msgmerge']),
            array('from_code',       'e',  InputOption::VALUE_REQUIRED, 'The encoding of the .pot-files', $defaults['from_code']),
            array('author',          'a',  InputOption::VALUE_REQUIRED, 'The author of the translations', $defaults['author']),
            array('package_name',    'pn', InputOption::VALUE_REQUIRED, 'The package name', $defaults['package_name']),
            array('package_version', 'pv', InputOption::VALUE_REQUIRED, 'The package version', $defaults['package_version']),
            array('email',           'm',  InputOption::VALUE_REQUIRED, 'The email address of the author', $defaults['email']),
            array('keywords',        'k',  InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The keywords to search for in the source files', $defaults['keywords']),
        );
    }

}

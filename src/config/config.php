<?php

return array(

	/*
	|--------------------------------------------------------------------------
	| Locales
	|--------------------------------------------------------------------------
	| 
	| This is a list of all locales you plan to use. Just make sure that they
	| are installed on the system. 
	|
	| The first value is the default and the base of your translations. 
	| In this case: en_US. 
	| 
	| If you use msgmerge the package will try to create the folder structure
	| for the additional locales.
	| 
	*/

	'locales' => array('en_US'),

	/*
	|--------------------------------------------------------------------------
	| Textdomain
	|--------------------------------------------------------------------------
	|
	| The textdomain should be unique for each project
	| 
	| A good example of this would be 'yourdomain.tld'
	| 
	| The default is messages
	|
	*/
	
	'textdomain' => 'messages',

	/*
	|--------------------------------------------------------------------------
	| Path
	|--------------------------------------------------------------------------
	|
	| The locale path is relative to the app/-path for your laravel project
	| You are better of not changing this, but you can ofcourse do it.
	|
	| Don't include a trailing slash
	| 
	| The default is app/lang which ships with laravel by design
	|
	*/

	'path' => 'lang',

	/*
	|--------------------------------------------------------------------------
	| Default encoding
	|--------------------------------------------------------------------------
	|
	| Since charsets is a bitch from system to system we have to specify a list 
	| of them to make sure that we don't break any systems.
	| 
	| This can also be a string if you know your system.
	| 
	*/

	'encoding' => array('utf8', 'UTF8', 'utf-8', 'UTF-8'),

	/*
	|--------------------------------------------------------------------------
	| Set locale
	|--------------------------------------------------------------------------
	| 
	| On some systems the default locale is already set and does not
	| need to be set again. This is probably just a personal preference.
	|
	| This enables the locale to be set in the constructor.
	| 
	*/

	'setlocale' => false,

	/*
	|--------------------------------------------------------------------------
	| Cache
	|--------------------------------------------------------------------------
	| 
	| This is the path where the compiled blade view files will end up.
	| relative to app/
	|
	| Don't include a trailing slash.
	|
	*/

	'cache' => 'storage/gettext',

	/*
	|--------------------------------------------------------------------------
	| Cleanup
	|--------------------------------------------------------------------------
	| 
	| This cleans up the compiled view files after execution. This is just
	| a personal preference. 
	|
	*/

	'cleanup' => false,


	/*
	|--------------------------------------------------------------------------
	| Xgettext configuration
	|--------------------------------------------------------------------------
	| Xgettext is the component that goes through the files and finds gettext
	| references and generates POT-files that can be translated.
	|
	*/

	'xgettext' => array(

		/*
		|--------------------------------------------------------------------------
		| Xgettext Binary
		|--------------------------------------------------------------------------
		| If your xgettext-binary is called something else, you can change the 
		| name here. 
		| 
		*/

		'binary' => 'xgettext', 

		/*
		|--------------------------------------------------------------------------
		| Path
		|--------------------------------------------------------------------------
		| If xgettext is not in your path, you can specify the path to it. Do not 
		| include the binary itself. Do not include a trailing slash.
		| 
		| The default is ''.
		| 
		*/

		'binary_path' => '',

        /*
		|--------------------------------------------------------------------------
		| Comments
		|--------------------------------------------------------------------------
		| Determines which docbloc comment are included as a remarks for translators      
        | the default is [TRANSLATORS]
		| 
		*/
        
        'comments' => 'TRANSLATORS',

        /*
		|--------------------------------------------------------------------------
		| Force PO
		|--------------------------------------------------------------------------
		| force creation of .po(t) file, even if no strings were found
        | the default is 'true'
		| 
		*/

        'force_po' => true,

        /*
		|--------------------------------------------------------------------------
		| No location
		|--------------------------------------------------------------------------
		| Do not write '#: filename:line' lines 
		| Default is 'false', which leaves those lines in the pot-file
		|
		*/

        'no_location' => false,

        /*
		|--------------------------------------------------------------------------
		| From Code
		|--------------------------------------------------------------------------
		| Set the encoding of the files. If it's empty ASCII will be used. 
		| The default is UTF-8. 
		|
		*/

        'from_code' => 'UTF-8',

        /*
		|--------------------------------------------------------------------------
		| Author
		|--------------------------------------------------------------------------
		| The author of the POT-file.
		|
		*/

        'author' => 'Your Name',

        /*
		|--------------------------------------------------------------------------
		| Package name
		|--------------------------------------------------------------------------
		| The package name that will be included in the POT-file. 
		|
		*/

        'package_name' => 'Your project',

        /*
        |--------------------------------------------------------------------------
        | Package version
        |--------------------------------------------------------------------------
        | The package version that will be included in the POT-file. 
        |
        */
		
        'package_version' => 'v1.0.0',

        /*
        |--------------------------------------------------------------------------
        | Email
        |--------------------------------------------------------------------------
        | The email address that will be included in the POT-file
        |
        */
        
        'email' => 'you@yourdomain.tld',

        /*
        |--------------------------------------------------------------------------
        | Keywords
        |--------------------------------------------------------------------------
        | Keywords that xgettext looks for. You can add your own if it's a valid 
        | PHP-function. 
        |
        */
        'keywords' => array(
            '_',                // shorthand for gettext
            'gettext',          // the default php gettext function
            'dgettext:2',       // accepts plurals, uses the second argument passed to dgettext as a translation string
            'dcgettext:2',      // accepts plurals, uses the second argument passed to dcgettext as a translation string
            'ngettext:1,2',     // accepts plurals, uses the first and second argument passed to ngettext as a translation string
            'dngettext:2,3',    // accepts plurals, used the second and third argument passed to dngettext as a translation string
            'dcngettext:2,3',   // accepts plurals, used the second and third argument passed to dcngettext as a translation string
            '_n:1,2',           // a custom l4gettext shorthand for ngettext (supports plurals)
        ),
	),

	/*
	|--------------------------------------------------------------------------
	| Msgmerge configuration
	|--------------------------------------------------------------------------
	| Msgmerge merges two PO files together. This makes updating the language
	| files easier, since you do not have to manually merge the translation to 
	| keep the existing translations. 
	|
	| It's enabled by default.
	*/

	'msgmerge' => array(

		/*
		|--------------------------------------------------------------------------
		| Enabled
		|--------------------------------------------------------------------------
		| If you turn this to false, the package will not try to merge existing 
		| translations with new ones. 
		|
		*/

		'enabled' => true,

		/* 
		|--------------------------------------------------------------------------
		| Binary
		|--------------------------------------------------------------------------
		| If your msgmerge-binary is called something else, you can change the
		| name here.
		|
		*/

		'binary' => 'msgmerge',

		/*
		|--------------------------------------------------------------------------
		| Binary path
		|--------------------------------------------------------------------------
		| If msgmerge is not in your PATH you can specify a path to it here.
		| 
		| Do not include a trailing slash.
		|
		*/

		'binary_path' => '',

	),
);
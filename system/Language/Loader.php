<?php namespace CodeIgniter\Language;

use Config\Services;

class Loader
{
	/**
	 * Stores the retrieved language lines
	 * from files for faster retrieval on
	 * second use.
	 *
	 * @var array
	 */
	protected $language = [];

	/**
	 * The current language/locale to work with.
	 *
	 * @var string
	 */
	protected $locale;

	/**
	 * Boolean value whether the intl
	 * libraries exist on the system.
	 *
	 * @var bool
	 */
	protected $intlSupport = false;

	/**
	 * Stores filenames that have been
	 * loaded so that we don't load them again.
	 *
	 * @var array
	 */
	protected $loadedFiles = [];

	//--------------------------------------------------------------------

	public function __construct()
	{
		$request = Services::request();

		$this->locale        = $request->getLocale();

		if (class_exists('\MessageFormatter'))
		{
			$this->intlSupport = true;
		};

		unset($request);
	}

	//--------------------------------------------------------------------

	/**
	 *
	 *
	 * @param string $line
	 * @param array  $args
	 *
	 * @return string
	 */
	public function getLine(string $line, array $args = []): string
	{
		// Parse out the file name and the actual alias.
		// Will load the language file and strings.
		$line = $this->parseLine($line);

		$output = $this->language[$line] ?? '';

		// Do advanced message formatting here
		// if the 'intl' extension is available.
		if ($this->intlSupport && count($args))
		{
			$output = \MessageFormatter::formatMessage($this->locale, $line, $args);
		}

		return $output;
	}

	//--------------------------------------------------------------------

	/**
	 * Parses the language string which should include the
	 * filename as the first segment (separated by period).
	 *
	 * @param string $line
	 *
	 * @return string
	 */
	protected function parseLine(string $line): string
	{
		if (strpos($line, '.') === false)
		{
			throw new \InvalidArgumentException('No language file specified in line: '. $line);
		}

		$file = substr($line, 0, strpos($line, '.'));
		$line = substr($line, strlen($file));

		if (! array_key_exists($this->languages[$this->locale][$line]))
		{
			$this->load($file, $this->locale);
		}

		return $line;
	}

	//--------------------------------------------------------------------

	/**
	 * Loads a language file in the current locale. If $return is true,
	 * will return the file's contents, otherwise will merge with
	 * the existing language lines.
	 *
	 * @param string $file
	 * @param string $locale
	 * @param bool   $return
	 *
	 * @return array
	 */
	public function load(string $file, string $locale, bool $return = false): array
	{
		if (in_array($file, $this->loadedFiles))
		{
			return;
		}

		$lang = [];

		$path = APPPATH."Language/{$locale}/{$file}.php";

		// First check the app's Language folder
		if (is_file($path))
		{
			$lang = require $path;
		}

		// @todo - should look into loading from other locations, also probably...

		// Don't load it more than once.
		$this->loadedFiles[] = $file;

		if ($return)
		{
			return $lang;
		}

		// Merge our string
		$this->language = array_merge($this->language, $lang);
	}

	//--------------------------------------------------------------------

}

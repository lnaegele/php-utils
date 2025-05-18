<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\Appsettings;

use Exception;

class AppsettingsManager
{
	private static $appsettings = null;

    public function __construct(
        private string $directory,
    ) {}
	
	public function get($name) {
		$this->checkLoaded();
        if (!array_key_exists($name, self::$appsettings)) {
			throw new Exception("Appsettings file does not contain key '".$name."'.");
		}
		return self::$appsettings[$name];
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T
	 */
	public function getConfiguration($className): object {
        $clazz = new \ReflectionClass($className);

        // Check constructor has no properties
        $cParams = $clazz->getConstructor()?->getParameters();
        if ($cParams != null && count($cParams)>0) {
            throw new \Exception('Class ' . $className . ' can not be instantiated as it does not provide a parameterless constructor.');
        }

        $result = $clazz->newInstanceArgs();

		$data = $this->get($clazz->getShortName());
		foreach ($data as $key => $value) $result->{$key} = $value;
		return $result;
	}

	public function getAppSettingsDirectory(): string {
		return $this->directory;
	}
	
	private function checkLoaded() {
        $serverName = $_SERVER['SERVER_NAME'];
        $fileName = "appsettings.$serverName.json";
        if (!file_exists("$this->directory/$fileName")) {
            $fileName = "appsettings.json";
        }

        $filePath = "$this->directory/$fileName";
		if (!file_exists($filePath)) throw new Exception("Appsettings file '".$filePath."' does not exist.");
		$content = json_decode(file_get_contents($filePath), true);
		if ($content==null) throw new Exception("Malformed appsettings file '".$filePath."'.");
		self::$appsettings = $content;
	}
}
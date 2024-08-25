<?php
namespace FeatherBB\Core\Interfaces;

class Request extends \Statical\BaseProxy
{
	static public function isPost(): bool
	{
		return self::getInstance()->getMethod() === 'POST';
	}

	public static function getParsedBodyParam(string $key, $default = null) {
		$request = self::getInstance();
		$postParams = $request->getParsedBody();
		$result = $default;

		if (is_array($postParams) && isset($postParams[$key])) {
			$result = $postParams[$key];
		} else if (is_object($postParams) && property_exists($postParams, $key)) {
			$result = $postParams->$key;
		}
		return $result;
	}
}

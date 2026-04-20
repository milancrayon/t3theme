<?php

namespace Crayon\T3theme\Utilities;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Obj implements SingletonInterface
{

	const END_OF_RECURSION = '%#EOR#%';

	/**
	 * 	@var mixed
	 */
	protected $obj;
	public function __construct(mixed $obj = null)
    {
        $this->obj = $obj;
    }
    /**
     * @param bool $addClass
     * @param int $depth
     * @param array<int, string> $fields
     * @param mixed $obj
     * @return array<string, mixed>|mixed
     */
    public function toArray($addClass = true, $depth = 5, array $fields = [], $obj = null)
    {

		if ($obj === null) {
			return null;
		}

		$isSimpleType = $this->isSimpleType(gettype($obj));
		$isStorage = !$isSimpleType && $this->isStorage($obj);

		if ($depth < 0) {
			return $isSimpleType && !is_array($obj) ? $obj : self::END_OF_RECURSION;
		}

		if ($isSimpleType && !is_array($obj)) {
			return $obj;
		}

		$type = is_object($obj) ? get_class($obj) : false;
		$final = [];
		$depth--;

		if (is_a($obj, \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult::class)) {

			$obj = $obj->toArray();

		} else if (is_a($obj, \DateTime::class)) {

			// DateTime in UTC konvertieren
			$utc = $obj->getTimestamp();
			return $utc;

		} else if ($isStorage) {

			// StorageObject in einfaches Array konvertieren
			$obj = $this->forceArray($obj);
			if ($addClass)
				$obj['__class'] = ObjectStorage::class;

		} else if ($type) {

			// Alle anderen Objekte
			$keys = $fields ?: $this->getKeys($obj);

			foreach ($keys as $field) {
				$val = $this->prop($field, $obj);
				$val = $this->toArray($addClass, $depth, $fields, $val);
				if ($val === self::END_OF_RECURSION)
					continue;
				$final[$field] = $val;
			}
			return $final;

		}

		foreach ($obj as $k => $v) {
			$val = $this->toArray($addClass, $depth, $fields, $v);
			if ($val === self::END_OF_RECURSION)
				continue;
			$final[$k] = $val;
		}

		return $final;
	}

    /**
     * @param mixed $type
     * @return bool
     */
    public function isSimpleType($type): bool
    {
		return in_array($type, ['array', 'string', 'float', 'double', 'integer', 'int', 'boolean', 'bool']);
	}

    /**
     * @param mixed $obj
     * @return bool
     */
    public function isStorage($obj): bool
    {
        if (!is_object($obj)) {
            return false;
        }
		$type = get_class($obj);
		return is_a($obj, ObjectStorage::class) || $type == LazyObjectStorage::class || $type == ObjectStorage::class || $type == \TYPO3\CMS\Extbase\Persistence\ObjectStorage::class;
	}

    /**
     * @param object|string $obj
     * @return array<int, string>
     */
    public function getKeys(object|string $obj): array
    {
		if (is_string($obj) && class_exists($obj)) {
			$obj = new $obj();
		}
		$keys = [];
		if (is_object($obj)) {
			return ObjectAccess::getGettablePropertyNames($obj);
		}
		return [];
	}

    /**
     * @param mixed $key
     * @param mixed $obj
     * @return mixed
     */
    public function prop($key, $obj)
    {
		if ($key == '')
			return '';
		$key = explode('.', $key);
		if (count($key) == 1)
			return $this->accessSingleProperty($key[0], $obj);

		foreach ($key as $k) {
			$obj = $this->accessSingleProperty($k, $obj);
			if (!$obj)
				return '';
		}
		return $obj;
	}

    /**
     * @param mixed $key
     * @param mixed $obj
     * @return mixed
     */
    public function accessSingleProperty($key, $obj)
    {
		if ($key == '')
			return '';

		if (is_object($obj)) {

			if (is_numeric($key)) {
				$obj = $this->forceArray($obj);
				return $obj[intval($key)];
			}

			$gettable = ObjectAccess::isPropertyGettable($obj, $key);
			if ($gettable)
				return ObjectAccess::getProperty($obj, $key);

			$camelCaseKey = GeneralUtility::underscoredToLowerCamelCase($key);
			$gettable = ObjectAccess::isPropertyGettable($obj, $camelCaseKey);
			if ($gettable)
				return ObjectAccess::getProperty($obj, $camelCaseKey);

			return $obj->$key ?? null;

		} else {
			if (is_array($obj))
				return $obj[$key] ?? null;
		}
		return [];
	}

	/**
	 * Force conversion to array
	 *
	 * @param mixed $obj
	 * @return array<int|string, mixed>
	 */
    public function forceArray(mixed $obj): array
    {
        if ($obj instanceof ObjectStorage) {
            return iterator_to_array($obj);
        }
        if (is_object($obj) && method_exists($obj, 'toArray')) {
            return $obj->toArray();
        }
        if (is_iterable($obj)) {
            return iterator_to_array($obj);
        }
        if (is_object($obj)) {
            return get_object_vars($obj);
        }
        return (array)$obj;
    }
}
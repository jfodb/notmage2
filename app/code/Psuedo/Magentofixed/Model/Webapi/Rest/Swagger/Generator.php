<?php
/**
 * Created by PhpStorm.
 * User: ppostma
 * Date: 2019-04-01
 * Time: 14:55
 */

namespace Psuedo\Magentofixed\Model\Webapi\Rest\Swagger;


class Generator extends \Magento\Webapi\Model\Rest\Swagger\Generator
{

	protected $logger;


	/**
	 * Initialize dependencies.
	 *
	 * @param \Magento\Webapi\Model\Cache\Type\Webapi $cache
	 * @param \Magento\Framework\Reflection\TypeProcessor $typeProcessor
	 * @param \Magento\Framework\Webapi\CustomAttribute\ServiceTypeListInterface $serviceTypeList
	 * @param \Magento\Webapi\Model\ServiceMetadata $serviceMetadata
	 * @param Authorization $authorization
	 * @param SwaggerFactory $swaggerFactory
	 * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
	 */
	public function __construct(
		\Magento\Webapi\Model\Cache\Type\Webapi $cache,
		\Magento\Framework\Reflection\TypeProcessor $typeProcessor,
		\Magento\Framework\Webapi\CustomAttribute\ServiceTypeListInterface $serviceTypeList,
		\Magento\Webapi\Model\ServiceMetadata $serviceMetadata,
		Authorization $authorization,
		SwaggerFactory $swaggerFactory,
		ProductMetadataInterface $productMetadata,
		\Psr\Log\LoggerInterface $log
	) {
		$this->logger = $log;

		parent::__construct(
			$cache,
			$typeProcessor,
			$serviceTypeList,
			$serviceMetadata,
			$authorization,
			$swaggerFactory,
			$productMetadata
		);
	}


	/**
	 * Generate parameters based on method data
	 *
	 * @param array $httpMethodData
	 * @param string $operationId
	 * @return array
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	protected function generateMethodParameters($httpMethodData, $operationId)
	{
		$bodySchema = [];
		$parameters = [];

		$phpMethodData = $httpMethodData[Converter::KEY_METHOD];
		/** Return nothing if necessary fields are not set */
		if (!isset($phpMethodData['interface']['in']['parameters'])
			|| !isset($httpMethodData['uri'])
			|| !isset($httpMethodData['httpOperation'])
		) {
			return [];
		}

		foreach ($phpMethodData['interface']['in']['parameters'] as $parameterName => $parameterInfo) {
			/** Omit forced parameters */
			if (isset($httpMethodData['parameters'][$parameterName]['force'])
				&& $httpMethodData['parameters'][$parameterName]['force']
			) {
				continue;
			}

			if (!isset($parameterInfo['type'])) {
				return [];
			}
			$description = isset($parameterInfo['documentation']) ? $parameterInfo['documentation'] : null;

			/** Get location of parameter */
			if (strpos($httpMethodData['uri'], '{' . $parameterName . '}') !== false) {
				$parameters[] = $this->generateMethodPathParameter($parameterName, $parameterInfo, $description);
			} elseif (strtoupper($httpMethodData['httpOperation']) === 'GET') {
				$parameters = $this->generateMethodQueryParameters(
					$parameterName,
					$parameterInfo,
					$description,
					$parameters
				);
			} else {
				$bodySchema = $this->generateBodySchema(
					$parameterName,
					$parameterInfo,
					$description,
					$bodySchema
				);
			}
		}

		/**
		 * Add all the path params that don't correspond directly the PHP parameters
		 */
		preg_match_all('#\\{([^\\{\\}]*)\\}#', $httpMethodData['uri'], $allPathParams);
		$remainingPathParams = array_diff(
			$allPathParams[1],
			array_keys($phpMethodData['interface']['in']['parameters'])
		);
		foreach ($remainingPathParams as $pathParam) {
			$parameters[] = [
				'name' => $pathParam,
				'in' => 'path',
				'type' => 'string',
				'required' => true
			];
		}

		if ($bodySchema) {
			$bodyParam = [];
			$bodyParam['name'] = $operationId . 'Body';
			$bodyParam['in'] = 'body';
			$bodyParam['schema'] = $bodySchema;
			$parameters[] = $bodyParam;
		}
		return $parameters;
	}

	/**
	 * Creates an array for the given query parameter
	 *
	 * @param string $name
	 * @param string $type
	 * @param string $description
	 * @param bool|null $required
	 * @return array
	 */
	protected function createQueryParam($name, $type, $description, $required = null)
	{
		$param = [
			'name' => $name,
			'in' => 'query',
		];

		$param = array_merge($param, $this->getObjectSchema($type, $description));

		if (isset($required)) {
			$param['required'] = $required;
		}
		return $param;
	}


	protected function snakeCaseDefinitions($definitions)
	{
		foreach ($definitions as $name => $vals) {
			if (!empty($vals['properties'])) {
				$definitions[$name]['properties'] = $this->convertArrayToSnakeCase($vals['properties']);
			}
			if (!empty($vals['required'])) {
				$snakeCaseRequired = [];
				foreach ($vals['required'] as $requiredProperty) {
					$snakeCaseRequired[] = SimpleDataObjectConverter::camelCaseToSnakeCase($requiredProperty);
				}
				$definitions[$name]['required'] = $snakeCaseRequired;
			}
		}
		return $definitions;
	}

	/**
	 * Converts associative array's key names from camelCase to snake_case, recursively.
	 *
	 * @param array $properties
	 * @return array
	 */
	protected function convertArrayToSnakeCase($properties)
	{
		foreach ($properties as $name => $value) {
			$snakeCaseName = SimpleDataObjectConverter::camelCaseToSnakeCase($name);
			if (is_array($value)) {
				$value = $this->convertArrayToSnakeCase($value);
			}
			unset($properties[$name]);
			$properties[$snakeCaseName] = $value;
		}
		return $properties;
	}

	/**
	 * Recursively generate the query param names for a complex type
	 *
	 * @param string $name
	 * @param string $type
	 * @param string $prefix
	 * @param bool $isArray
	 * @return string[]
	 */
	protected function handleComplex($name, $type, $prefix, $isArray)
	{
		$parameters = $this->typeProcessor->getTypeData($type)['parameters'];
		$queryNames = [];
		foreach ($parameters as $subParameterName => $subParameterInfo) {
			$subParameterType = $subParameterInfo['type'];
			$subParameterDescription = isset($subParameterInfo['documentation'])
				? $subParameterInfo['documentation']
				: null;
			$subPrefix = $prefix
				? $prefix . '[' . $name . ']'
				: $name;
			if ($isArray) {
				$subPrefix .= self::ARRAY_SIGNIFIER;
			}
			$queryNames = array_merge(
				$queryNames,
				$this->getQueryParamNames($subParameterName, $subParameterType, $subParameterDescription, $subPrefix)
			);
		}
		return $queryNames;
	}

	/**
	 * Generate the query param name for a primitive type
	 *
	 * @param string $name
	 * @param string $prefix
	 * @return string
	 */
	protected function handlePrimitive($name, $prefix)
	{
		return $prefix
			? $prefix . '[' . $name . ']'
			: $name;
	}


	/**
	 * Convert path parameters from :param to {param}
	 *
	 * @param string $uri
	 * @return string
	 */
	protected function convertPathParams($uri)
	{
		$parts = explode('/', $uri);
		$count = count($parts);
		for ($i=0; $i < $count; $i++) {
			if (strpos($parts[$i], ':') === 0) {
				$parts[$i] = '{' . substr($parts[$i], 1) . '}';
			}
		}
		return implode('/', $parts);
	}

	/**
	 * Generate method path parameter
	 *
	 * @param string $parameterName
	 * @param array $parameterInfo
	 * @param string $description
	 * @return string[]
	 */
	protected function generateMethodPathParameter($parameterName, $parameterInfo, $description)
	{
		$param = [
			'name' => $parameterName,
			'in' => 'path',
			'type' => $this->getSimpleType($parameterInfo['type']),
			'required' => true
		];
		if ($description) {
			$param['description'] = $description;
			return $param;
		}
		return $param;
	}

	/**
	 * Generate method query parameters
	 *
	 * @param string $parameterName
	 * @param array $parameterInfo
	 * @param string $description
	 * @param array $parameters
	 * @return array
	 */
	protected function generateMethodQueryParameters($parameterName, $parameterInfo, $description, $parameters)
	{
		$queryParams = $this->getQueryParamNames($parameterName, $parameterInfo['type'], $description);
		if (count($queryParams) === 1) {
			// handle simple query parameter (includes the 'required' field)
			$parameters[] = $this->createQueryParam(
				$parameterName,
				$parameterInfo['type'],
				$description,
				$parameterInfo['required']
			);
		} else {
			/**
			 * Complex query parameters are represented by a set of names which describes the object's fields.
			 *
			 * Omits the 'required' field.
			 */
			foreach ($queryParams as $name => $queryParamInfo) {
				$parameters[] = $this->createQueryParam(
					$name,
					$queryParamInfo['type'],
					$queryParamInfo['description']
				);
			}
		}
		return $parameters;
	}

	/**
	 * Generate body schema
	 *
	 * @param string $parameterName
	 * @param array $parameterInfo
	 * @param string $description
	 * @param array $bodySchema
	 * @return array
	 */
	protected function generateBodySchema($parameterName, $parameterInfo, $description, $bodySchema)
	{
		$required = isset($parameterInfo['required']) ? $parameterInfo['required'] : null;
		/*
		 * There can only be one body parameter, multiple PHP parameters are represented as different
		 * properties of the body.
		 */
		if ($required) {
			$bodySchema['required'][] = $parameterName;
		}
		$bodySchema['properties'][$parameterName] = $this->getObjectSchema(
			$parameterInfo['type'],
			$description
		);
		$bodySchema['type'] = 'object';
		return $bodySchema;
	}

	/**
	 * Generate method 200 response
	 *
	 * @param array $parameters
	 * @param array $responses
	 * @return array
	 */
	protected function generateMethodSuccessResponse($parameters, $responses)
	{
		if (isset($parameters['result']) && is_array($parameters['result'])) {
			$description = '';
			if (isset($parameters['result']['documentation'])) {
				$description = $parameters['result']['documentation'];
			}
			$schema = [];
			if (isset($parameters['result']['type'])) {
				$schema = $this->getObjectSchema($parameters['result']['type'], $description);
			}

			// Some methods may have a non-standard HTTP success code.
			$specificResponseData = $parameters['result']['response_codes']['success'] ?? [];
			// Default HTTP success code to 200 if nothing has been supplied.
			$responseCode = $specificResponseData['code'] ?? '200';
			// Default HTTP response status to 200 Success if nothing has been supplied.
			$responseDescription = $specificResponseData['description'] ?? '200 Success.';

			$responses[$responseCode]['description'] = $responseDescription;
			if (!empty($schema)) {
				$responses[$responseCode]['schema'] = $schema;
			}
		}
		return $responses;
	}

	/**
	 * Generate method exception error responses
	 *
	 * @param array $exceptionClass
	 * @param array $responses
	 * @return array
	 */
	protected function generateMethodExceptionErrorResponses($exceptionClass, $responses)
	{
		$httpCode = '500';
		$description = 'Internal Server error';
		$this->logger->alert('Error detected in Webap Rest Generator');
		if (is_subclass_of($exceptionClass, \Magento\Framework\Exception\LocalizedException::class)) {
			// Map HTTP codes for LocalizedExceptions according to exception type
			if (is_subclass_of($exceptionClass, \Magento\Framework\Exception\NoSuchEntityException::class)) {
				$httpCode = WebapiException::HTTP_NOT_FOUND;
				$description = '404 Not Found';
			} elseif (is_subclass_of($exceptionClass, \Magento\Framework\Exception\AuthorizationException::class)
				|| is_subclass_of($exceptionClass, \Magento\Framework\Exception\AuthenticationException::class)
			) {
				$httpCode = WebapiException::HTTP_UNAUTHORIZED;
				$description = self::UNAUTHORIZED_DESCRIPTION;
			} else {
				// Input, Expired, InvalidState exceptions will fall to here
				$httpCode = WebapiException::HTTP_BAD_REQUEST;
				$description = '400 Bad Request';

				$this->logger->notice('Expired or invalid state in Webapi Rest Generator');
			}
		}
		$responses[$httpCode]['description'] = $description;
		$responses[$httpCode]['schema']['$ref'] = self::ERROR_SCHEMA;

		return $responses;
	}





	protected function generateMethodResponses($methodData)
	{
		$responses = [];

		if (isset($methodData['interface']['out']['parameters'])
			&& is_array($methodData['interface']['out']['parameters'])
		) {
			$parameters = $methodData['interface']['out']['parameters'];
			$responses = $this->generateMethodSuccessResponse($parameters, $responses);
		}

		/** Handle authorization exceptions that may not be documented */
		if (isset($methodData['resources'])) {
			foreach ($methodData['resources'] as $resourceName) {
				if ($resourceName !== 'anonymous') {
					$responses[WebapiException::HTTP_UNAUTHORIZED]['description'] = self::UNAUTHORIZED_DESCRIPTION;
					$responses[WebapiException::HTTP_UNAUTHORIZED]['schema']['$ref'] = self::ERROR_SCHEMA;
					break;
				}
			}
		}

		if (isset($methodData['interface']['out']['throws'])
			&& is_array($methodData['interface']['out']['throws'])
		) {
			foreach ($methodData['interface']['out']['throws'] as $exceptionClass) {
				$responses = $this->generateMethodExceptionErrorResponses($exceptionClass, $responses);
			}
		}
		$responses['default']['description'] = 'Unexpected error';
		$responses['default']['schema']['$ref'] = self::ERROR_SCHEMA;

		return $responses;
	}


}
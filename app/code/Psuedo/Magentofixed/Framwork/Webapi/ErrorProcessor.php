<?php
/**
 * Created by PhpStorm.
 * User: ppostma
 * Date: 2019-04-10
 * Time: 10:06
 */

namespace Psuedo\Magentofixed\Framwork\Webapi;

//use Magento\Framework\App\Filesystem\DirectoryList;
//use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Magento\Framework\Exception\AggregateExceptionInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
//use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Webapi\Exception as WebapiException;
use Magento\Framework\Exception\InputException;

class ErrorProcessor extends \Magento\Framework\Webapi\ErrorProcessor
{

	public function maskException(\Exception $exception)
	{

		$isDevMode = $this->_appState->getMode() === State::MODE_DEVELOPER;
		$stackTrace = $isDevMode ? $exception->getTraceAsString() : null;

		//Could-not-save wraps a parent exception that contains the details we need to work with.
		if($exception instanceof \Magento\Framework\Exception\CouldNotSaveException){
			$parentException = $exception;
			//not all exceptions are set to contain a previous
			$exception = $exception->getPrevious() ?? $exception;

		}

		if($exception instanceof \Stripe\Error\Card) {
			$httpCode = 422;
			$minorException = true;
		}

		if ($exception instanceof WebapiException) {
			$maskedException = $exception;
		} elseif ($exception instanceof LocalizedException) {
			// Map HTTP codes for LocalizedExceptions according to exception type
			if ($exception instanceof NoSuchEntityException) {
				$httpCode = WebapiException::HTTP_NOT_FOUND;
			} elseif (($exception instanceof AuthorizationException)
				|| ($exception instanceof AuthenticationException)) {

				$httpCode = WebapiException::HTTP_UNAUTHORIZED;

			} elseif ($exception instanceof InputException) {
				$httpCode = WebapiException::HTTP_BAD_REQUEST;

			} elseif ($exception instanceof \Magento\Payment\Gateway\Command\CommandException
					|| $exception instanceof \Magento\Payment\Gateway\Http\ClientException) {

					//remote client errors

					if (stripos($exception->getMessage(), 'unable to read response, or response is empty') !== false) {
						//payment or other gateway failed, message from ZendClientException of no_response
						$httpCode = 504; //remote timeout
					} elseif (preg_match('/rejecte|refuse|decline/i', $exception->getMessage())) {
						//remote or payment did not agree with information given, fix and resend
						$httpCode = 409;
					} else {
						$httpCode = $exception->getCode() ?? WebapiException::HTTP_INTERNAL_ERROR;
						//log this to track it down
						$this->_logger->notice($exception);
					}

			} else {
				// Input, Expired, InvalidState exceptions will fall to here
				$httpCode = $exception->getCode(); // ?? 500;  //Unknown error as default.
				if(empty($httpCode)) {
					if(preg_match('/lost|gone|expired/', $exception->getMessage()))
						$httpCode = 410; //Gone
					else
						$httpCode = WebapiException::HTTP_INTERNAL_ERROR; //default
				}


				$this->_logger->notice("WebAPI Error Processor report");

				//$this->_logger->notice($exception); //this is recording NULL, manually gather details

				/* This isn't needed if notice logs exception */
				$this->_logger->notice(get_class($exception));
				$this->_logger->notice($exception->getMessage());
				$this->_logger->notice($exception->getFile() . ':' . $exception->getLine());
				$this->_logger->notice($exception->getTraceAsString());

				if (method_exists($exception, 'getPrevious')) {
					$internalException = $exception->getPrevious();
					if (!empty($internalException)) {
						$this->_logger->notice("Internal Exception");
						$this->_logger->notice(get_class($internalException));
						$this->_logger->notice($internalException->getMessage());
						$this->_logger->notice($internalException->getFile() . ':' . $internalException->getLine());
						$this->_logger->notice($internalException->getTraceAsString());
					}
				}

				$tmp = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				ob_start();
				print_r($tmp);
				$stack = ob_get_clean();
				$this->_logger->notice($stack);
				/* Above is not needed if notice logs exception */

				$this->_logger->notice("/end WebAPI Error Processor Report");

			}

			if ($exception instanceof AggregateExceptionInterface
				|| (isset($parentException) && $parentException instanceof AggregateExceptionInterface)) {
				if (isset($parentException) && $parentException instanceof AggregateExceptionInterface)
					//switch back now
					$exception = $parentException;

				$errors = $exception->getErrors();
			} else {
				$errors = null;
			}

			$maskedException = new WebapiException(
				new Phrase($exception->getRawMessage()),
				$exception->getCode(),
				$httpCode,
				$exception->getParameters(),
				get_class($exception),
				$errors,
				$stackTrace
			);
		} else {
			$message = $exception->getMessage();
			$code = $exception->getCode();
			if(empty($code) && isset($httpCode))
				$code = $httpCode;

			//if not in Dev mode, make sure the message and code is masked for unanticipated exceptions
			if (!$isDevMode && (!($exception instanceof \Stripe\Error\Card) || $code > 499)) {
				/** Log information about actual exception */
				$reportId = $this->_critical($exception);
				$message = sprintf(self::INTERNAL_SERVER_ERROR_MSG, $reportId);
				$code = 0;
			}

			if(empty($httpCode))
				$httpCode = WebapiException::HTTP_INTERNAL_ERROR;

			$maskedException = new WebapiException(
				new Phrase($message),
				$code,
				$httpCode,
				[],
				'',
				null,
				$stackTrace
			);
		}
		return $maskedException;
	}
}

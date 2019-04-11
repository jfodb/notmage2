<?php
/**
 * Created by PhpStorm.
 * User: ppostma
 * Date: 2019-04-10
 * Time: 10:06
 */

namespace Psuedo\Magentofixed\Framwork\Webapi;


use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Magento\Framework\Exception\AggregateExceptionInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Webapi\Exception as WebapiException;


class ErrorProcessor extends \Magento\Framework\Webapi\ErrorProcessor
{

	public function maskException(\Exception $exception)
	{
		$isDevMode = $this->_appState->getMode() === State::MODE_DEVELOPER;
		$stackTrace = $isDevMode ? $exception->getTraceAsString() : null;

		if ($exception instanceof WebapiException) {
			$maskedException = $exception;
		} elseif ($exception instanceof LocalizedException) {
			// Map HTTP codes for LocalizedExceptions according to exception type
			if ($exception instanceof NoSuchEntityException) {
				$httpCode = WebapiException::HTTP_NOT_FOUND;
			} elseif (($exception instanceof AuthorizationException)
				|| ($exception instanceof AuthenticationException)
			) {
				$httpCode = WebapiException::HTTP_UNAUTHORIZED;
			} else {
				// Input, Expired, InvalidState exceptions will fall to here
				$httpCode = 409;  //Conflict, please retry.
				$this->_logger->notice("tripped 400 in Error Processor");

				$this->_logger->notice(get_class($exception));
				$this->_logger->notice($exception->getMessage());
				$this->_logger->notice($exception->getFile() .':'. $exception->getLine());
				$this->_logger->notice($exception->getTraceAsString());

				$tmp = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				ob_start();
				print_r($tmp);
				$stack = ob_get_clean();
				$this->_logger->notice($stack);
				$this->_logger->notice("end 400 error in processor");

			}
			$this->_logger->alert($exception);  //but there is nothing logged here...

			if ($exception instanceof AggregateExceptionInterface) {
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
			//if not in Dev mode, make sure the message and code is masked for unanticipated exceptions
			if (!$isDevMode) {
				/** Log information about actual exception */
				$reportId = $this->_critical($exception);
				$message = sprintf(self::INTERNAL_SERVER_ERROR_MSG, $reportId);
				$code = 0;
			}
			$maskedException = new WebapiException(
				new Phrase($message),
				$code,
				WebapiException::HTTP_INTERNAL_ERROR,
				[],
				'',
				null,
				$stackTrace
			);
		}
		return $maskedException;
	}
}
<?php
/*
 * This file is part of the long/framework package.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Haomiao\Slim;

use Haomiao\Slim\Exception\BadRequest;
use Haomiao\Slim\Exception\BadRequestHandler;
use Haomiao\Slim\Exception\Exception;
use Haomiao\Slim\Exception\ExceptionHandler;
use Haomiao\Slim\Exception\MethodNotAllowed;
use Haomiao\Slim\Exception\MethodNotAllowedHandler;
use Haomiao\Slim\Exception\NotFound;
use Haomiao\Slim\Exception\NotFoundHandler;
use Haomiao\Slim\Exception\Message;
use Haomiao\Slim\Exception\MessageHandler;
use Slim\Exception\MethodNotAllowedException as SlimMethodNotAllowedException;
use Slim\Exception\NotFoundException as SlimNotFoundException;

/**
 * Application settings.
 * 
 * @package Haomiao\Slim
 * @since   1.0.0
 */
class Setting extends DataObject implements SettingInterface
{

}

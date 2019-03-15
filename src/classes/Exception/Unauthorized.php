<?php 
/*
 * This file is part of long/framework.
 *
 * (c) Sinpe Inc. <dev@sinpe.com>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

namespace Haomiao\Slim\Exception;

/**
 * 401.
 * 
 * @package Sinpe\Framework
 * @since   1.0.0
 */
class Unauthorized extends BadRequest
{
    /**
     * __construct
     */
    public function __construct()
    {
        $args = func_get_args();

        array_unshift($args, -401);
        array_unshift($args, 'Unauthorized');

        call_user_func_array('parent::__construct', $args);
    }
}

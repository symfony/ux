<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Filter;

use Symfony\UX\DataTable\TypeInterface;

/**
 * A filter type only configures options: the form field ("form_type",
 * "form_options") and how its data is applied to the query ("query_path",
 * "operator", "apply").
 *
 * @author Simon Daigre <simon@daig.re>
 */
interface FilterTypeInterface extends TypeInterface
{
}

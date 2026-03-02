<?php

namespace AhmedArafat\AllInOne\Exceptions;

use AhmedArafat\AllInOne\Traits\ApiResponser;
use AhmedArafat\AllInOne\Traits\JsonApiResponser;
use Exception;

class ExceptionWithJsonApiResponser extends Exception
{
    use JsonApiResponser;
}
